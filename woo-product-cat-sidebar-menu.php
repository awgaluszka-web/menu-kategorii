<?php
/**
 * Plugin Name: Woo Menu Kategorii Produktów (Sidebar)
 * Description: Widget do WooCommerce: wyświetla pełne drzewo kategorii produktów z rozwinięciem gałęzi bieżącej kategorii; rodzeństwo zawsze widoczne w miejscu parenta.
 * Version: 1.3.0
 * Author: Adam Gałuszka
 */

if (!defined('ABSPATH')) exit;

define('WPCSM_VERSION', '1.3.0');

class WPCSM_Product_Cat_Sidebar_Menu_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'wpcsm_product_cat_sidebar_menu',
			__('Woo: Menu kategorii (sidebar)', 'wpcsm'),
			['description' => __('Pełne drzewo kategorii z rozwinięciem gałęzi bieżącej kategorii i widocznym rodzeństwem.', 'wpcsm')]
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

		$current_term = $this->get_current_product_cat_term();
		$current_id   = $current_term ? (int) $current_term->term_id : 0;

		if (!$current_id) {
			return;
		}

		// Załaduj styl tylko gdy widget rzeczywiście się renderuje
		wp_enqueue_style(
			'wpcsm-style',
			plugin_dir_url(__FILE__) . 'assets/wpcsm.css',
			[],
			WPCSM_VERSION
		);

		$title      = isset($instance['title']) ? $instance['title'] : '';
		$hide_empty = !empty($instance['hide_empty']);
		$show_count = !empty($instance['show_count']);

		echo $args['before_widget'];

		if ($title) {
			echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
		}

		// Łańcuch przodków od korzenia do bieżącej kategorii
		$ancestors = get_ancestors($current_id, 'product_cat');
		$chain_ids = array_reverse($ancestors); // od root-ancestor do parenta
		$chain_ids[] = $current_id;

		// Link "Cofnij do ..." – pokaż, jeśli bieżąca kategoria ma rodzica
		if ($current_term && !empty($current_term->parent)) {
			$parent = get_term((int) $current_term->parent, 'product_cat');
			if ($parent && !is_wp_error($parent)) {
				$parent_url = get_term_link($parent, 'product_cat');
				if (!is_wp_error($parent_url)) {
					echo '<div class="wpcsm-back">';
					echo '<a class="wpcsm-back__link" href="' . esc_url($parent_url) . '">';
					echo esc_html__('← Cofnij do', 'wpcsm') . ' ' . esc_html($parent->name);
					echo '</a>';
					echo '</div>';
				}
			}
		}

		// Pobierz wszystkie top-level kategorie
		$top_cats = get_terms([
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => $hide_empty,
			'orderby'    => 'name',
			'order'      => 'ASC',
		]);

		echo '<ul class="wpcsm-menu wpcsm-menu--product-cat">';

		if (!is_wp_error($top_cats) && !empty($top_cats)) {
			foreach ($top_cats as $top_cat) {
				$top_id = (int) $top_cat->term_id;
				if (in_array($top_id, $chain_ids, true)) {
					// Renderuj rozwinięty branch (gałąź do bieżącej kategorii)
					echo $this->render_expanded_branch($top_id, $chain_ids, $current_id, $hide_empty, $show_count);
				} else {
					// Zwykły top-level item bez rozwijania
					echo '<li class="wpcsm-item">';
					echo $this->render_term_link($top_cat, $show_count);
					echo '</li>';
				}
			}
		}

		echo '</ul>';

		echo $args['after_widget'];
	}

	/**
	 * Renderuje gałąź drzewa od danego węzła w dół do current_id.
	 * Na każdym poziomie przodka pokazuje wszystkie jego dzieci (rodzeństwo),
	 * rozwijając rekurencyjnie tylko tego, który należy do łańcucha chain_ids.
	 */
	private function render_expanded_branch($term_id, $chain_ids, $current_id, $hide_empty, $show_count) {
		$term = get_term($term_id, 'product_cat');
		if (!$term || is_wp_error($term)) {
			return '';
		}

		$is_current  = ($term_id === $current_id);
		$is_ancestor = !$is_current && in_array($term_id, $chain_ids, true);

		$classes = ['wpcsm-item'];
		if ($is_current) {
			$classes[] = 'is-current';
		} elseif ($is_ancestor) {
			$classes[] = 'is-ancestor';
		}

		$html  = '<li class="' . implode(' ', $classes) . '">';
		$html .= $this->render_term_link($term, $show_count);

		if ($is_current) {
			// Pokaż dzieci bieżącej kategorii (1 poziom niżej), jeśli ma
			$children = get_terms([
				'taxonomy'   => 'product_cat',
				'parent'     => $term_id,
				'hide_empty' => $hide_empty,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]);
			if (!is_wp_error($children) && !empty($children)) {
				$html .= '<ul class="wpcsm-sub">';
				foreach ($children as $child) {
					$html .= '<li class="wpcsm-item">';
					$html .= $this->render_term_link($child, $show_count);
					$html .= '</li>';
				}
				$html .= '</ul>';
			}
		} else {
			// Przodek – znajdź następny węzeł w łańcuchu i rozwiń wszystkich jego braci
			$chain_index   = array_search($term_id, $chain_ids, true);
			$next_in_chain = isset($chain_ids[$chain_index + 1]) ? (int) $chain_ids[$chain_index + 1] : null;

			if ($next_in_chain !== null) {
				$children = get_terms([
					'taxonomy'   => 'product_cat',
					'parent'     => $term_id,
					'hide_empty' => $hide_empty,
					'orderby'    => 'name',
					'order'      => 'ASC',
				]);
				if (!is_wp_error($children) && !empty($children)) {
					$html .= '<ul class="wpcsm-sub">';
					foreach ($children as $child) {
						$child_id = (int) $child->term_id;
						if ($child_id === $next_in_chain) {
							// Rekurencja dla następnego w łańcuchu
							$html .= $this->render_expanded_branch($child_id, $chain_ids, $current_id, $hide_empty, $show_count);
						} else {
							// Rodzeństwo – render bez rozwijania
							$html .= '<li class="wpcsm-item">';
							$html .= $this->render_term_link($child, $show_count);
							$html .= '</li>';
						}
					}
					$html .= '</ul>';
				}
			}
		}

		$html .= '</li>';
		return $html;
	}

	private function render_term_link($term, $show_count) {
		$url = get_term_link($term, 'product_cat');
		if (is_wp_error($url)) $url = '#';

		$name = esc_html($term->name);
		$count = $show_count ? ' <span class="wpcsm-count">(' . (int)$term->count . ')</span>' : '';

		return '<a class="wpcsm-link" href="' . esc_url($url) . '">' . $name . $count . '</a>';
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

			$deepest  = null;
			$max_depth = -1;

			foreach ($terms as $t) {
				$anc   = get_ancestors($t->term_id, 'product_cat');
				$depth = is_array($anc) ? count($anc) : 0;

				if ($depth > $max_depth || ($depth === $max_depth && $deepest && (int)$t->term_id > (int)$deepest->term_id)) {
					$deepest   = $t;
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
