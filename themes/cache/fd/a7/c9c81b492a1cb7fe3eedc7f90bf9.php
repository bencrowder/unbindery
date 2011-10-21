<?php

/* header.html */
class __TwigTemplate_fda7c9c81b492a1cb7fe3eedc7f90bf9 extends Twig_Template
{
    protected function doGetParent(array $context)
    {
        return false;
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $context = array_merge($this->env->getGlobals(), $context);

        // line 1
        $this->env->loadTemplate("header_shared.html")->display($context);
        // line 2
        echo "
<body>
\t<div id=\"header_container\">
\t\t<div id=\"header\">
\t\t\t<div id=\"logo\"><a href=\"";
        // line 6
        echo twig_escape_filter($this->env, $this->getContext($context, 'siteroot'), "html");
        echo "/\"><img src=\"";
        echo twig_escape_filter($this->env, $this->getContext($context, 'siteroot'), "html");
        echo "/img/logo.jpg\" alt=\"Unbindery\" /></a></div>
\t\t\t<ul id=\"nav\">
\t\t\t\t";
        // line 8
        if ($this->getAttribute($this->getContext($context, 'user'), "loggedin", array(), "any", false)) {
            // line 9
            echo "\t\t\t\t\t<li>Logged in as <span class=\"username\">";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'user'), "username", array(), "any", false), "html");
            echo "</span></li>
\t\t\t\t\t<li><a href=\"";
            // line 10
            echo twig_escape_filter($this->env, $this->getContext($context, 'siteroot'), "html");
            echo "/dashboard\">Dashboard</a></li>
\t\t\t\t\t<li><a href=\"";
            // line 11
            echo twig_escape_filter($this->env, $this->getContext($context, 'siteroot'), "html");
            echo "/projects\">Projects</a></li>
\t\t\t\t\t";
            // line 12
            if ($this->getAttribute($this->getContext($context, 'user'), "admin", array(), "any", false)) {
                // line 13
                echo "\t\t\t\t\t<li><a href=\"";
                echo twig_escape_filter($this->env, $this->getContext($context, 'siteroot'), "html");
                echo "/admin\">Admin</a></li>
\t\t\t\t\t";
            }
            // line 15
            echo "\t\t\t\t\t<li><a href=\"";
            echo twig_escape_filter($this->env, $this->getContext($context, 'siteroot'), "html");
            echo "/settings\">Settings</a></li>
\t\t\t\t\t<li><a href=\"";
            // line 16
            echo twig_escape_filter($this->env, $this->getContext($context, 'siteroot'), "html");
            echo "/logout\">Logout</a></li>
\t\t\t\t";
        } else {
            // line 18
            echo "\t\t\t\t\t<li><a href=\"";
            echo twig_escape_filter($this->env, $this->getContext($context, 'siteroot'), "html");
            echo "/signup\">Sign Up</a></li>
\t\t\t\t\t<li><a href=\"";
            // line 19
            echo twig_escape_filter($this->env, $this->getContext($context, 'siteroot'), "html");
            echo "/login\">Login</a></li>
\t\t\t\t";
        }
        // line 21
        echo "\t\t\t</ul>
\t\t</div>
\t</div>
";
    }

    public function getTemplateName()
    {
        return "header.html";
    }

    public function isTraitable()
    {
        return false;
    }
}
