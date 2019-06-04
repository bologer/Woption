<?php

namespace Woption\Base;

/**
 * Class BaseHtml is base HTML helper class.
 *
 * Originally taken from Yii2 framework:
 *
 * @link https://github.com/yiisoft/yii2/blob/master/framework/helpers/BaseHtml.php
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 * @package Woption
 */
class BaseHtml {
	/**
	 * @var string Regular expression used for attribute name validation.
	 * @since 2.0.12
	 */
	public static $attributeRegex = '/(^|.*\])([\w\.\+]+)(\[.*|$)/u';
	/**
	 * @var array list of void elements (element name => 1)
	 * @see http://www.w3.org/TR/html-markup/syntax.html#void-element
	 */
	public static $voidElements = [
		'area'    => 1,
		'base'    => 1,
		'br'      => 1,
		'col'     => 1,
		'command' => 1,
		'embed'   => 1,
		'hr'      => 1,
		'img'     => 1,
		'input'   => 1,
		'keygen'  => 1,
		'link'    => 1,
		'meta'    => 1,
		'param'   => 1,
		'source'  => 1,
		'track'   => 1,
		'wbr'     => 1,
	];
	/**
	 * @var array the preferred order of attributes in a tag. This mainly affects the order of the attributes
	 * that are rendered by [[renderTagAttributes()]].
	 */
	public static $attributeOrder = [
		'type',
		'id',
		'class',
		'name',
		'value',
		'href',
		'src',
		'srcset',
		'form',
		'action',
		'method',
		'selected',
		'checked',
		'readonly',
		'disabled',
		'multiple',
		'size',
		'maxlength',
		'width',
		'height',
		'rows',
		'cols',
		'alt',
		'title',
		'rel',
		'media',
	];
	/**
	 * @var array list of tag attributes that should be specially handled when their values are of array type.
	 * In particular, if the value of the `data` attribute is `['name' => 'xyz', 'age' => 13]`, two attributes
	 * will be generated instead of one: `data-name="xyz" data-age="13"`.
	 * @since 2.0.3
	 */
	public static $dataAttributes = [ 'data', 'data-ng', 'ng' ];

	/**
	 * Generates a complete HTML tag.
	 *
	 * @param string|bool|null $name the tag name. If $name is `null` or `false`, the corresponding content will be rendered without any tag.
	 * @param string $content the content to be enclosed between the start and end tags. It will not be HTML-encoded.
	 * If this is coming from end users, you should consider [[encode()]] it to prevent XSS attacks.
	 * @param array $options the HTML tag attributes (HTML options) in terms of name-value pairs.
	 * These will be rendered as the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
	 * If a value is null, the corresponding attribute will not be rendered.
	 *
	 * For example when using `['class' => 'my-class', 'target' => '_blank', 'value' => null]` it will result in the
	 * html attributes rendered like this: `class="my-class" target="_blank"`.
	 *
	 * See [[renderTagAttributes()]] for details on how attributes are being rendered.
	 *
	 * @return string the generated HTML tag
	 * @see beginTag()
	 * @see endTag()
	 */
	public static function tag ( $name, $content = '', $options = [] ) {
		if ( $name === null || $name === false ) {
			return $content;
		}
		$html = "<$name" . static::renderTagAttributes( $options ) . '>';

		return isset( static::$voidElements[ strtolower( $name ) ] ) ? $html : "$html$content</$name>";
	}

	/**
	 * Renders the HTML tag attributes.
	 *
	 * Attributes whose values are of boolean type will be treated as
	 * [boolean attributes](http://www.w3.org/TR/html5/infrastructure.html#boolean-attributes).
	 *
	 * Attributes whose values are null will not be rendered.
	 *
	 * The values of attributes will be HTML-encoded using [[encode()]].
	 *
	 * The "data" attribute is specially handled when it is receiving an array value. In this case,
	 * the array will be "expanded" and a list data attributes will be rendered. For example,
	 * if `'data' => ['id' => 1, 'name' => 'woption']`, then this will be rendered:
	 * `data-id="1" data-name="woption"`.
	 * Additionally `'data' => ['params' => ['id' => 1, 'name' => 'woption'], 'status' => 'ok']` will be rendered as:
	 * `data-params='{"id":1,"name":"woption"}' data-status="ok"`.
	 *
	 * @param array $attributes attributes to be rendered. The attribute values will be HTML-encoded using [[encode()]].
	 *
	 * @return string the rendering result. If the attributes are not empty, they will be rendered
	 * into a string with a leading white space (so that it can be directly appended to the tag name
	 * in a tag. If there is no attribute, an empty string will be returned.
	 * @see addCssClass()
	 */
	public static function renderTagAttributes ( $attributes ) {
		if ( count( $attributes ) > 1 ) {
			$sorted = [];
			foreach ( static::$attributeOrder as $name ) {
				if ( isset( $attributes[ $name ] ) ) {
					$sorted[ $name ] = $attributes[ $name ];
				}
			}
			$attributes = array_merge( $sorted, $attributes );
		}
		$html = '';
		foreach ( $attributes as $name => $value ) {
			if ( is_bool( $value ) ) {
				if ( $value ) {
					$html .= " $name";
				}
			} elseif ( is_array( $value ) ) {
				if ( in_array( $name, static::$dataAttributes ) ) {
					foreach ( $value as $n => $v ) {
						if ( is_array( $v ) ) {
							$html .= " $name-$n='" . json_encode( $v ) . "'";
						} else {
							$html .= " $name-$n=\"" . static::encode( $v ) . '"';
						}
					}
				} elseif ( $name === 'class' ) {
					if ( empty( $value ) ) {
						continue;
					}
					$html .= " $name=\"" . static::encode( implode( ' ', $value ) ) . '"';
				} elseif ( $name === 'style' ) {
					if ( empty( $value ) ) {
						continue;
					}
					$html .= " $name=\"" . static::encode( static::cssStyleFromArray( $value ) ) . '"';
				} else {
					$html .= " $name='" . json_encode( $value ) . "'";
				}
			} elseif ( $value !== null ) {
				$html .= " $name=\"" . static::encode( $value ) . '"';
			}
		}

		return $html;
	}

	/**
	 * Converts a CSS style array into a string representation.
	 *
	 * For example,
	 *
	 * ```php
	 * print_r(Html::cssStyleFromArray(['width' => '100px', 'height' => '200px']));
	 * // will display: 'width: 100px; height: 200px;'
	 * ```
	 *
	 * @param array $style the CSS style array. The array keys are the CSS property names,
	 * and the array values are the corresponding CSS property values.
	 *
	 * @return string the CSS style string. If the CSS style is empty, a null will be returned.
	 */
	public static function cssStyleFromArray ( array $style ) {
		$result = '';
		foreach ( $style as $name => $value ) {
			$result .= "$name: $value; ";
		}

		// return null if empty to avoid rendering the "style" attribute
		return $result === '' ? null : rtrim( $result );
	}

	/**
	 * Encodes the given value into a JSON string HTML-escaping entities so it is safe to be embedded in HTML code.
	 *
	 * The method enhances `json_encode()` by supporting JavaScript expressions.
	 * In particular, the method will not encode a JavaScript expression that is
	 * represented in terms of a [[JsExpression]] object.
	 *
	 * Note that data encoded as JSON must be UTF-8 encoded according to the JSON specification.
	 * You must ensure strings passed to this method have proper encoding before passing them.
	 *
	 * @param mixed $value the data to be encoded
	 *
	 * @return string the encoding result
	 * @since 2.0.4
	 */
	public static function htmlEncode ( $value ) {
		return static::encode( $value, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS );
	}

	/**
	 * Encodes special characters into HTML entities.
	 *
	 * @param string $content the content to be encoded
	 * @param bool $doubleEncode whether to encode HTML entities in `$content`. If false,
	 * HTML entities in `$content` will not be further encoded.
	 *
	 * @return string the encoded content
	 * @see decode()
	 * @see http://www.php.net/manual/en/function.htmlspecialchars.php
	 */
	public static function encode ( $content, $doubleEncode = true ) {
		return htmlspecialchars( $content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode );
	}
}