<?php
require_once dirname(__FILE__) . '/../mathjax.inc.php';

class MathJaxTest extends PHPUnit_Framework_TestCase
{
    private $_inline_html_format = "<span class='mathjax-eq ' style='%s'>\\( %s \\)</span>";
    private $_html_format = "<div class='mathjax-eq img_margin' style='%s'>%s</div>";

    public function test_inline()
    {
        $styles = array(
            array(),
            array('color: red'),
            array('color: red; font-size: 150%'),
            array('color: red', ' font-size: 200%'),
            array(),  // reset style
            );

        foreach ($styles as $style) {
            $testcases = $this->_make_inline_testcases($style);
            $args = array_merge($style, array(''));
            $this->assertEmpty(MathJax::inline($args), 'set style: ' . json_encode($args));
            foreach ($testcases as $testcase) {
                $this->assertEquals($testcase[0], MathJax::inline($testcase[1]), $testcase[2]);
            }
        }

        // exceptions
        // inline arguments shoud be CSS styles
        $this->assertRegExp('/usage:/', MathJax::inline(array('a + b', '')), '&mathjax(a + b);');
        // recognize arguments as an equation when one of them includes '\'
        $this->assertRegExp('/usage:/', MathJax::inline(array('color: blue\\9;', '')), '&mathjax(color: blue\\9;)');
        // set style 'a: b' (Cannot determine which it is CSS styles or equations)
        $this->assertEmpty(MathJax::inline(array('a: b', '')), '&mathjax(a: b);');
    }

    public function test_convert()
    {
        $styles = array(
            array(),
            array('color: red'),
            array('color: red; font-size: 150%'),
            array('color: red', ' text-align: right'),
            array(),  // reset style
            );

        foreach ($styles as $style) {
            $testcases = $this->_make_testcases($style);
            $args = $style;
            $this->assertEmpty(MathJax::convert($args), 'set style: ' . json_encode($args));
            foreach ($testcases as $testcase) {
                $this->assertEquals($testcase[0], MathJax::convert($testcase[1]), $testcase[2]);
            }
        }

        // exceptions
        // recognize arguments as an equation when one of them includes '\'
        $this->assertNotEmpty(MathJax::convert(array('color: blue\\9;', '')), '#mathjax(color: blue\\9;)');
        // set style 'a: b' (Cannot determine which it is CSS styles or equations)
        $this->assertEmpty(MathJax::convert(array('a: b', '')), '#mathjax(a: b)');
    }

    private function _make_inline_testcases($style)
    {
        $html_format = $this->_inline_html_format;
        $style = MathJax::make_inline_style($style);

        // array(expected HTML, arguments, message)
        return array(
            array(sprintf($html_format, $style, 'z = f(x, y)'),
                  array('z = f(x, y)'),
                  '&mathjax{z = f(x, y)}; or &mathjax(){z = f(x, y)};'
                ),
            array(sprintf($html_format, $style, 'z = f(x, y)'),
                  array("\n", 'z = f(x, y)'),
                  '$z = f(x, y)$'
                ),
            array(sprintf($html_format, 'color: blue', 'z = f(x, y)'),
                  array('color: blue', 'z = f(x, y)'),
                  '&mathjax(color: blue){z = f(x, y)}; or &mathjax(){z = f(x, y)};'
                ),
            array(sprintf($html_format, 'color: blue; font-size: 120%', 'z = f(x, y)'),
                  array('color: blue; font-size: 120%', 'z = f(x, y)'),
                  '&mathjax(color: blue; font-size: 120%){z = f(x, y)}; or &mathjax(){z = f(x, y)};'
                ),
            array(sprintf($html_format, 'color: blue; font-size: 120%', 'z = f(x, y)'),
                  array('color: blue', ' font-size: 120%', 'z = f(x, y)'),
                  '&mathjax(color: blue, font-size: 120%){z = f(x, y)}; or &mathjax(){z = f(x, y)};'
                ),
            );
    }

    private function _make_testcases($style)
    {
        $html_format = $this->_html_format;
        $style = MathJax::make_style($style);

        // array(expected HTML, arguments, message)
        return array(
            array(sprintf($html_format, $style, '\\[ z = f(x, y) \\]'),
                  array('z = f(x', ' y)'),
                  '#mathjax(z = f(x, y))'
                ),
            array(sprintf($html_format, $style, '\\[ z = f(x, y) \\]'),
                  array('\\[ z = f(x', ' y) \\]'),
                  '\\[ z = f(x, y) \\]'
                ),
            array(sprintf($html_format, $style, '\\begin{equation} z = f(x, y) \\end{equation}'),
                  array('\\begin{equation} z = f(x', ' y) \\end{equation}'),
                  '\\begin{equation} z = f(x, y) \\end{equation}'
                ),
            array(sprintf($html_format, $style, '\\[ \\alpha \\]'),
                  array('\\alpha'),
                  '#mathjax(\\alpha)'
                ),
            );
    }
}
