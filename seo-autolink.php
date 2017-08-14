<?php
/*
Plugin Name: SEO Autolink
Plugin URI:  http://techjunkie.com
Description: Autolink post content to category  pages
Author: TechJunkie.com
Version: 1.00
Author URI: http://techjunkie.com/
License: GPL
*/

require_once 'tax-meta-class/Tax-meta-class.php';
require_once 'simple-html-dom/HtmlDomParser.php';

class SEO_Autolink {
	public function __construct() {
		$this->setup_category_meta_box();
		add_filter( 'the_content', array( $this, 'transform_post_keywords' ) );
	}

	public function setup_category_meta_box() {
		$meta = new Tax_Meta_Class(array(
			'id' => 'tj_autolink_category_meta_box',
			'title' => 'Autolink Settings',
			'pages' => array('category'),
		));

		$meta->addRepeaterBlock('autolink_keywords', array(
			'inline' => true,
			'name' => 'Keywords',
			'fields' => array(
				$meta->addText('keyword', array('name' => 'Keyword'), true)
			)
		));

		$meta->Finish();
	}

	private function get_keyword_link_mappings() {
		$terms = get_categories(array(
			'hide_empty' => false
		));

		$mappings = array();

		foreach ($terms as $term) {
			$keywords = get_term_meta($term->term_id, 'autolink_keywords', true);

			if ($keywords) {
				foreach ($keywords as $keyword_fields) {
					$keyword = $keyword_fields['keyword'];
					$mappings[mb_strtolower($keyword)] = '<a href="' . get_term_link($term->term_id) . '" title="__KEYWORD__" class="tax-context-link">__KEYWORD__</a>';
				}
			}
		}

		uksort($mappings, function ($a, $b) {
			return mb_strlen($a) > mb_strlen($b);
		});

		return $mappings;
	}

	public function transform_post_keywords($content) {
		$mappings = $this->get_keyword_link_mappings();

		return $this->replace_keywords($content, $mappings, array('a', 'h2'));
	}

	private function replace_keywords($html_content, $mappings, $excludedParents = array()) {
		$html = \Sunra\PhpSimple\HtmlDomParser::str_get_html($html_content);

		foreach ($html->find('text') as $element) {
			if (!in_array($element->parent()->tag, $excludedParents)) {
				$counter = 0;
				$replacements = array();
				$patterns = array();
				foreach ($mappings as $search => $replace) {
					$regex = '/\b' . preg_quote($search) . '\b/i';

					$element->innertext = preg_replace_callback($regex, function ($matches) use ($replace, &$counter, &$replacements, &$patterns) {
						$match = reset($matches);

						$pattern = '%%' . $counter . '%%';
						$patterns[] = $pattern;
						$replacements[] = str_replace('__KEYWORD__', $match, $replace);
						$counter++;
						return $pattern;
					}, $element->innertext);
				}

				$element->innertext = str_replace($patterns, $replacements, $element->innertext);
			}
		}

		return (string)$html;
	}
}

$seo_autolink = new SEO_Autolink();
