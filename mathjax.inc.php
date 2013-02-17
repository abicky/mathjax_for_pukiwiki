<?php
/**
 * MathJax Plugin for PukiWiki
 * $Id: mathjax.inc.php,v 0.02 2013/02/17 20:20 abicky Exp $
 *
 * @author     abicky
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link       https://github.com/abicky/mathjax_for_pukiwiki
 * @version    0.02
 */

define('MATHJAX_URL', 'http://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS_HTML');
define('MATHJAX_USAGE', '#mathjax(tex mathmatical expression) or #mathjax(css styles)');
define('MATHJAX_INLINE_USAGE', '&mathjax([css styles]){[tex mathmatical expression]};');
define('MATHJAX_DEFAULT_ALIGN', 'left');  // 'left', 'center', 'right'
define('MATHJAX_CONF', '
MathJax.Hub.Config({
    displayAlign: "inherit",
    TeX: {
        Macros: {
            bm: ["\\\\boldsymbol{#1}", 1],
            argmax: ["\\\\mathop{\\\\rm arg\\\\,max}\\\\limits"],
            argmin: ["\\\\mathop{\\\\rm arg\\\\,min}\\\\limits"],
        },
        extensions: ["autobold.js", "color.js"],
        equationNumbers: {
             //autoNumber: "all"
        }
    },
    tex2jax: {
        ignoreClass: ".*",
        processClass: "mathjax-eq"
    }
});
');


class MathJax
{
    static private $_is_initialized = false;
    static private $_style = '';
    static private $_inline_style = '';
    // CSS rarely includes these symbols and LaTex equations frequently include them
    static private $_eq_symbols = array('\\', '_', '^', '=');

    static public function init()
    {
        global $head_tags;
        if (!self::$_is_initialized) {
            $head_tags[] = '<script type="text/javascript" src="' . MATHJAX_URL . '"></script>';
            $head_tags[] = '<script type="text/x-mathjax-config">' . MATHJAX_CONF . '</script>';
            self::$_is_initialized = true;
        }
    }

    static public function inline($args)
    {
        $eq = array_pop($args);
        if ($eq ||
            self::_is_equation($args)) {  // to show usage
            if (count($args) == 1 && $args[0] == "\n") {
                // called from Link_mathjax#toString, so make an empty array
                $args = array();
            }
            return self::_generate_inline_equation($eq, $args);
        } else {
            self::_set_inline_style($args);
            return;
        }
    }

    static public function convert($args)
    {
        if (self::_is_equation($args)) {
            $eq = implode(',', $args);
            return self::_generate_equation($eq);
        } else {
            self::_set_style($args);
            return;
        }
    }

    static public function make_style($args)
    {
        $style = implode(';', $args);
        if (strpos($style, "text-align") === false) {
            $style .= ';text-align:' . MATHJAX_DEFAULT_ALIGN . ';';
        }
        return $style;
    }

    static public function make_inline_style($args)
    {
        return implode(';', $args);
    }

    static private function _generate_equation($eq)
    {
        if ($eq) {
            $eq = ltrim($eq);
            if (substr($eq, 0, 1) != '\\' ||
                (strpos($eq, '\\[') !== 0 && strpos($eq, '\\begin') !== 0)) {
                $text = "\\[ $eq \\]";
            } else {
                $text = $eq;
            }
            $style = self::$_style;
        } else {
            $text = 'usage:' . MATHJAX_USAGE;
            $style = 'color: red;';
        }
        return self::_make_tag('div', $text, $style, 'img_margin');
    }

    static private function _generate_inline_equation($eq, $args)
    {
        if ($eq) {
            $text = "\\( $eq \\)";
            $style = empty($args) ? self::$_inline_style : self::make_inline_style($args);
        } else {
            $text = 'usage: ' . MATHJAX_INLINE_USAGE;
            $style = 'color: red;';
        }
        return self::_make_tag('span', $text, $style);
    }

    static private function _set_style($args)
    {
        self::$_style = self::make_style($args);
    }

    static private function _set_inline_style($args)
    {
        self::$_inline_style = self::make_inline_style($args);
    }

    static private function _make_tag($tag_name, $text, $style, $class = '')
    {
        return "<$tag_name class='mathjax-eq $class' style='$style'>$text</$tag_name>";
    }

    static private function _is_equation($args)
    {
        if (empty($args)) {
            // empty means reset of styles
            return false;
        }

        // check only the first argument
        if (strpos($args[0], ':') !== false && !self::_has_eq_symbol($args[0])) {
            return false;
        }

        return true;
    }

    static private function _has_eq_symbol($str)
    {
        foreach (self::$_eq_symbols as $symbol) {
            if (strpos($str, $symbol) !== false) {
                return true;
            }
        }
        return false;
    }

    // for compatibility to tex plugin
    static private function _extract_equation($args)
    {
        $is_array = is_array($args);
        if ($is_array) {
            $args = implode($args, ',');
        }
        // extract equation ('$foo $ bar$' -> 'foo $ bar')
        preg_match('/^\$(.*)\$.*?(.*)/', ltrim($args), $matches);
        $eq = $matches[1];
        $args = $matches[2];
        if ($is_array) {
            $args = explode(',', $args);
        }
        return array($eq, $args);
    }
}

function plugin_mathjax_init()
{
    MathJax::init();
}

function plugin_mathjax_inline()
{
    return MathJax::inline(func_get_args());
}

function plugin_mathjax_convert()
{
    return MathJax::convert(func_get_args());
}
