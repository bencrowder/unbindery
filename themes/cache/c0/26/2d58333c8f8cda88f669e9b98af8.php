<?php

/* header_shared.html */
class __TwigTemplate_c0262d58333c8f8cda88f669e9b98af8 extends Twig_Template
{
    protected function doGetParent(array $context)
    {
        return false;
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $context = array_merge($this->env->getGlobals(), $context);

        // line 1
        echo "<!doctype html>
<html>
<head>
\t<meta charset=\"utf-8\">
\t<title>Unbindery</title>

\t<link rel=\"shortcut icon\" href=\"";
        // line 7
        echo twig_escape_filter($this->env, $this->getContext($context, 'siteroot'), "html");
        echo "/img/favicon.png\" />

\t<link rel=\"stylesheet\" href=\"";
        // line 9
        echo twig_escape_filter($this->env, $this->getContext($context, 'siteroot'), "html");
        echo "/css/unbindery.css\" type=\"text/css\" media=\"screen\" charset=\"utf-8\" />

\t<script type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.6/jquery.min.js\"></script>
\t<script type=\"text/javascript\" src=\"";
        // line 12
        echo twig_escape_filter($this->env, $this->getContext($context, 'siteroot'), "html");
        echo "/lib/jquery.hotkeys.js\"></script>

\t";
        // line 14
        if ($this->getContext($context, 'includes')) {
            echo twig_escape_filter($this->env, $this->getContext($context, 'includes'), "html");
        }
        // line 15
        echo "
\t<script type=\"text/javascript\" src=\"";
        // line 16
        echo twig_escape_filter($this->env, $this->getContext($context, 'siteroot'), "html");
        echo "/js/config.js\"></script>
\t<script type=\"text/javascript\" src=\"";
        // line 17
        echo twig_escape_filter($this->env, $this->getContext($context, 'siteroot'), "html");
        echo "/js/unbindery.js\"></script>
</head>
";
    }

    public function getTemplateName()
    {
        return "header_shared.html";
    }

    public function isTraitable()
    {
        return false;
    }
}
