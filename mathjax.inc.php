<?php
/**
 * MathJax Plugin
 * $Id: mathjax.inc.php,v 0.00 2013/02/14 1:46 abicky Exp $
 *
 * @author     abicky
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link       https://github.com/abicky/mathjax_for_pukiwiki
 * @version    v 0.00
 */

define('MATHJAX_URL', 'http://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML');
define('MATHJAX_USAGE', '#mathjax($tex mathmatical expression$ [, css-styles])');
define('MATHJAX_INLINE_USAGE', '&mathjax([css-styles]){tex mathmatical expression};');
define('MATHJAX_DEFAULT_ALIGN', 'left');  // 'left', 'center', 'right'
define('MATHJAX_CONF', '
MathJax.Hub.Config({
    displayAlign: "inherit",
    TeX: {
        Macros: {
            bm: ["\\\\boldsymbol{#1}", 1],
            argmax: ["\\\\mathop{\\\\rm arg\\\\,max}\\\\limits" , 1],
            argmin: ["\\\\mathop{\\\\rm arg\\\\,min}\\\\limits" , 1],
        },
        extensions: ["autobold.js"],
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
    static private $is_initialized = false;

    static public function init()
    {
        global $head_tags;
        if (!self::$is_initialized) {
            $head_tags[] = '<script type="text/javascript" src="' . MATHJAX_URL . '"></script>';
            $head_tags[] = '<script type="text/x-mathjax-config">' . MATHJAX_CONF . '</script>';
            self::$is_initialized = true;
        }
    }

    static public function generate_equation($eq, $args)
    {
        if ($eq) {
            if (substr(ltrim($eq), 0, 1) != '\\') {
                $text = "\( $eq \)";
            } else {
                $text = $eq;
            }
            $style = self::make_style($args);
        } else {
            $text = 'usage:' . MATHJAX_USAGE;
            $style = 'color: red;';
        }
        return self::make_tag('div', $text, $style, 'img_margin');
    }

    static public function generate_inline_equation($eq, $args)
    {
        if ($eq) {
            $text = "\( $eq \)";
            $style = self::make_style($args);
        } else {
            $text = 'usage: ' . MATHJAX_INLINE_USAGE;
            $style = 'color: red;';
        }
        return self::make_tag('span', $text, $style);
    }

    static public function extract_equation($args)
    {
        // extract equation ('$foo $ bar$' -> 'foo $ bar')
        preg_match('/^\$(.*)\$.*?(.*)/', ltrim(implode($args, ',')), $matches);
        $eq = $matches[1];
        $args = explode(',', $matches[2]);
        return array($eq, $args);
    }

    static private function make_tag($tag_name, $text, $style, $class = '')
    {
        return "<$tag_name class='mathjax-eq $class' style='$style'>$text</$tag_name>";
    }

    static private function make_style($args)
    {
        if (!$args) {
            return '';
        }
        $style = implode(';', $args);
        if (strpos($style, "text-align") === false) {
            $style .= ';text-align:' . MATHJAX_DEFAULT_ALIGN;
        }
        return $style;
    }
}

function plugin_mathjax_init()
{
    MathJax::init();
}

function plugin_mathjax_inline()
{
    $args = func_get_args();
    $eq = array_pop($args);
    return MathJax::generate_inline_equation($eq, $args);
}

function plugin_mathjax_convert()
{
    $args = func_get_args();

    $eq = '';
    $has_dollar = substr(ltrim($args[0]), 0, 1) == '$';
    if ($has_dollar) {
        list($eq, $args) = MathJax::extract_equation($args);
    } else {
        $eq = implode(',', $args);
        $args = array();
    }

    return MathJax::generate_equation($eq, $args);
}
