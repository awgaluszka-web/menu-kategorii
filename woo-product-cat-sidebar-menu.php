<?php
/**
 * Plugin Name: Woo Menu Kategorii Produktów (Sidebar)
 * Description: Widget do WooCommerce: na kategorii głównej pokazuje wszystkie top-level i dzieci bieżącej; na niższym poziomie pokazuje rodzeństwo i dzieci bieżącej + link „Cofnij do".
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
			['description' => __('Menu kategorii: top-level pokazuje wszystkie główne + dzieci bieżącej; niższy poziom pokazuje rodzeństwo + dzieci bieżącej z linkiem Cofnij do.', 'wpcsm')]
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

		if (!$current_term || !$current_id) {
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

		$parent_id = (int) $current_term->parent;

		// Link "← Cofnij do ..." – pokaż tylko gdy bieżąca kategoria ma rodzica
		if ($parent_id !== 0) {
			$parent = get_term($parent_id, 'product_cat');
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

		echo '<ul class="wpcsm-menu wpcsm-menu--product-cat">';

		if ($parent_id === 0) {
			// Bieżąca jest TOP-LEVEL: pokaż wszystkie kategorie główne,
			// tylko dla bieżącej pokaż jej dzieci (1 poziom).
			$top_cats = get_terms([
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => $hide_empty,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]);

			if (!is_wp_error($top_cats) && !empty($top_cats)) {
				foreach ($top_cats as $top_cat) {
					$top_id     = (int) $top_cat->term_id;
					$is_current = ($top_id === $current_id);

					echo '<li class="wpcsm-item' . ($is_current ? ' is-current' : '') . '">';
					echo $this->render_term_link($top_cat, $show_count);

					if ($is_current) {
						$children = get_terms([
							'taxonomy'   => 'product_cat',
							'parent'     => $top_id,
							'hide_empty' => $hide_empty,
							'orderby'    => 'name',
							'order'      => 'ASC',
						]);
						if (!is_wp_error($children) && !empty($children)) {
							echo '<ul class="wpcsm-sub">';
							foreach ($children as $child) {
								echo '<li class="wpcsm-item">';
								echo $this->render_term_link($child, $show_count);
								echo '</li>';
							}
							echo '</ul>';
						}
					}

					echo '</li>';
				}
			}
		} else {
			// Bieżąca jest NIŻEJ: pokaż tylko rodzeństwo (dzieci parenta)
			// + pod bieżącą jej dzieci (1 poziom).
			$siblings = get_terms([
				'taxonomy'   => 'product_cat',
				'parent'     => $parent_id,
				'hide_empty' => $hide_empty,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]);

			if (!is_wp_error($siblings) && !empty($siblings)) {
				foreach ($siblings as $sibling) {
					$sibling_id = (int) $sibling->term_id;
					$is_current = ($sibling_id === $current_id);

					echo '<li class="wpcsm-item' . ($is_current ? ' is-current' : '') . '">';
					echo $this->render_term_link($sibling, $show_count);

					if ($is_current) {
						$children = get_terms([
							'taxonomy'   => 'product_cat',
							'parent'     => $current_id,
							'hide_empty' => $hide_empty,
							'orderby'    => 'name',
							'order'      => 'ASC',
						]);
						if (!is_wp_error($children) && !empty($children)) {
							echo '<ul class="wpcsm-sub">';
							foreach ($children as $child) {
								echo '<li class="wpcsm-item">';
								echo $this->render_term_link($child, $show_count);
								echo '</li>';
							}
							echo '</ul>';
						}
					}

					echo '</li>';
				}
			}
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
