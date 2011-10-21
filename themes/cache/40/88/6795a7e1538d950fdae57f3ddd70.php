<?php

/* footer.html */
class __TwigTemplate_40886795a7e1538d950fdae57f3ddd70 extends Twig_Template
{
    protected function doGetParent(array $context)
    {
        return false;
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $context = array_merge($this->env->getGlobals(), $context);

        // line 1
        echo "\t<div id=\"footer\">
\t\t<a href=\"http://bencrowder.net/coding/unbindery/\">Unbindery</a> was written by <a href=\"http://bencrowder.net/\">Ben Crowder</a>.
\t</div>
</body>
</html>
";
    }

    public function getTemplateName()
    {
        return "footer.html";
    }

    public function isTraitable()
    {
        return false;
    }
}
