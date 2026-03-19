<?php
/**
 * Plugin Name: Woo Menu Kategorii Produktów (Sidebar)
 * Description: Widget do WooCommerce: wyświetla tylko główne kategorie produktów oraz rozwiniętą gałąź bieżącej kategorii (ścieżka) + podkategorie bieżącej kategorii o jeden poziom niżej.
 * Version: 1.0.1
 * Author: Adam Gałuszka
 */

if (!defined('ABSPATH')) exit;

define('WPCSM_VERSION', '1.0.1');

class WPCSM_Product_Cat_Sidebar_Menu_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'wpcsm_product_cat_sidebar_menu',
			__('Woo: Menu kategorii (sidebar)', 'wpcsm'),
			['description' => __('Tylko główne kategorie + rozwinięta gałąź bieżącej kategorii i jej dzieci (1 poziom).', 'wpcsm')]
		);
	}

	public function form($instance) {
		$title = isset($instance['title']) ? $instance['title'] : __('Kategorie produktów', 'wpcsm');
		$hide_empty = isset($instance['hide_empty']) ? (bool)$instance['hide_empty'] : false;
		$show_count = isset($instance['show_count']) ? (bool)$instance['show_count'] : false;
		?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Tytuł:', 'wpcsm'); ?></label>
			<input class="widefat"
			       id="<?php echo esc_attr($this->get_field_id('title')); ?>"
			       name="<?php echo esc_attr($this->get_field_name('title')); ?>"
			       type="text"
			       value="<?php echo esc_attr($title); ?>">
		</p>
		<p>
			<input class="checkbox"
			       type="checkbox"
			       <?php checked($hide_empty); ?>
			       id="<?php echo esc_attr($this->get_field_id('hide_empty')); ?>"
			       name="<?php echo esc_attr($this->get_field_name('hide_empty')); ?>" />
			<label for="<?php echo esc_attr($this->get_field_id('hide_empty')); ?>"><?php esc_html_e('Ukrywaj puste kategorie', 'wpcsm'); ?></label>
		</p>
		<p>
			<input class="checkbox"
			       type="checkbox"
			       <?php checked($show_count); ?>
			       id="<?php echo esc_attr($this->get_field_id('show_count')); ?>"
			       name="<?php echo esc_attr($this->get_field_name('show_count')); ?>" />
			<label for="<?php echo esc_attr($this->get_field_id('show_count')); ?>"><?php esc_html_e('Pokazuj liczbę produktów', 'wpcsm'); ?></label>
		</p>
		<?php
	}

	public function update($new_instance, $old_instance) {
		$instance = [];
		$instance['title'] = isset($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
		$instance['hide_empty'] = !empty($new_instance['hide_empty']) ? 1 : 0;
		$instance['show_count'] = !empty($new_instance['show_count']) ? 1 : 0;
		return $instance;
	}

	public function widget($args, $instance) {
		// Pokazuj tylko na archiwach kategorii produktów i na stronach produktów
		if (!function_exists('is_product_category') || !function_exists('is_product')) {
			return;
		}
		if (!is_product_category() && !is_product()) {
			return;
		}

		// Załaduj styl tylko gdy widget rzeczywiście się renderuje
		wp_enqueue_style(
			'wpcsm-style',
			plugin_dir_url(__FILE__) . 'assets/wpcsm.css',
			[],
			WPCSM_VERSION
		);

		$title = isset($instance['title']) ? $instance['title'] : '';
		$hide_empty = !empty($instance['hide_empty']);
		$show_count = !empty($instance['show_count']);

		echo $args['before_widget'];

		if ($title) {
			echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
		}

		$current_term = $this->get_current_product_cat_term();
		$current_id = $current_term ? (int)$current_term->term_id : 0;

		// Łańcuch przodków (łącznie z bieżącą kategorią)
		$chain_ids = [];
		if ($current_id) {
			$ancestors = get_ancestors($current_id, 'product_cat'); // ID od rodzica do korzenia
			$chain_ids = array_reverse($ancestors);
			$chain_ids[] = $current_id;
		}

		$top_terms = get_terms([
			'taxonomy' => 'product_cat',
			'parent' => 0,
			'hide_empty' => $hide_empty,
			'orderby' => 'name',
			'order' => 'ASC',
		]);

		if (is_wp_error($top_terms) || empty($top_terms)) {
			echo $args['after_widget'];
			return;
		}

		echo '<ul class="wpcsm-menu wpcsm-menu--product-cat">';

		foreach ($top_terms as $top) {
			$top_id = (int)$top->term_id;
			$is_in_branch = $current_id && in_array($top_id, $chain_ids, true);
			$is_current = ($current_id === $top_id);

			echo '<li class="wpcsm-item wpcsm-item--top'
				. ($is_current ? ' is-current' : '')
				. ($is_in_branch && !$is_current ? ' is-ancestor' : '')
				. '">';

			echo $this->render_term_link($top, $show_count);

			// Jeśli bieżąca gałąź przechodzi przez tę kategorię główną -> renderuj rozwiniętą ścieżkę
			if ($is_in_branch) {
				echo $this->render_expanded_branch($top_id, $chain_ids, $current_id, $hide_empty, $show_count);
			}

			echo '</li>';
		}

		echo '</ul>';

		echo $args['after_widget'];
	}

	private function render_term_link($term, $show_count) {
		$url = get_term_link($term, 'product_cat');
		if (is_wp_error($url)) $url = '#';

		$name = esc_html($term->name);
		$count = $show_count ? ' <span class="wpcsm-count">(' . (int)$term->count . ')</span>' : '';

		return '<a class="wpcsm-link" href="' . esc_url($url) . '">' . $name . $count . '</a>';
	}

	/**
	 * Renderuje:
	 * - ścieżkę (gałąź) od kategorii głównej do bieżącej,
	 * - oraz dzieci bieżącej kategorii (tylko 1 poziom).
	 */
	private function render_expanded_branch($top_id, array $chain_ids, $current_id, $hide_empty, $show_count) {
		$html = '<ul class="wpcsm-sub">';

		// Znajdź kolejny element ścieżki w dół (jeśli istnieje)
		$next_id = 0;
		for ($i = 0; $i < count($chain_ids); $i++) {
			if ((int)$chain_ids[$i] === (int)$top_id) {
				$next_id = isset($chain_ids[$i + 1]) ? (int)$chain_ids[$i + 1] : 0;
				break;
			}
		}

		// Jeśli jest kolejny element ścieżki -> renderuj tylko tę ścieżkę (bez rodzeństwa)
		if ($next_id) {
			$next_term = get_term($next_id, 'product_cat');
			if ($next_term && !is_wp_error($next_term)) {
				$is_current = ((int)$next_id === (int)$current_id);

				$html .= '<li class="wpcsm-item'
					. ($is_current ? ' is-current' : ' is-ancestor')
					. '">';
				$html .= $this->render_term_link($next_term, $show_count);

				// Rekurencja w dół ścieżki aż do bieżącej kategorii
				$html .= $this->render_expanded_branch($next_id, $chain_ids, $current_id, $hide_empty, $show_count);

				$html .= '</li>';
			}

			$html .= '</ul>';
			return $html;
		}

		// Koniec ścieżki => jesteśmy na bieżącym węźle: pokaż jego dzieci (1 poziom)
		if ($current_id) {
			$children = get_terms([
				'taxonomy' => 'product_cat',
				'parent' => (int)$top_id,
				'hide_empty' => $hide_empty,
				'orderby' => 'name',
				'order' => 'ASC',
			]);

			if (!is_wp_error($children) && !empty($children)) {
				foreach ($children as $child) {
					$child_id = (int)$child->term_id;

					$html .= '<li class="wpcsm-item wpcsm-item--child'
						. ($child_id === (int)$current_id ? ' is-current' : '')
						. '">';
					$html .= $this->render_term_link($child, $show_count);
					$html .= '</li>';
				}
			}
		}

		$html .= '</ul>';
		return $html;
	}

	/**
	 * Ustalenie bieżącej kategorii:
	 * - na archiwum kategorii: ta kategoria
	 * - na produkcie: najgłębsza przypisana kategoria (największa liczba przodków)
	 */
	private function get_current_product_cat_term() {
		if (is_product_category()) {
			$term = get_queried_object();
			return ($term && isset($term->term_id)) ? $term : null;
		}

		if (is_product()) {
			$product_id = get_the_ID();
			$terms = get_the_terms($product_id, 'product_cat');
			if (empty($terms) || is_wp_error($terms)) {
				return null;
			}

			$deepest = null;
			$max_depth = -1;

			foreach ($terms as $t) {
				$anc = get_ancestors($t->term_id, 'product_cat');
				$depth = is_array($anc) ? count($anc) : 0;

				// Remis: wybierz wyższe term_id (prosty tie-breaker)
				if ($depth > $max_depth || ($depth === $max_depth && $deepest && (int)$t->term_id > (int)$deepest->term_id)) {
					$deepest = $t;
					$max_depth = $depth;
				} elseif ($depth > $max_depth && !$deepest) {
					$deepest = $t;
					$max_depth = $depth;
				}
			}

			return $deepest;
		}

		return null;
	}
}

add_action('widgets_init', function () {
	register_widget('WPCSM_Product_Cat_Sidebar_Menu_Widget');
});
