<?php

/* dashboard.html */
class __TwigTemplate_d3643fdb290cc3022dd1d2c0100d7cd0 extends Twig_Template
{
    protected function doGetParent(array $context)
    {
        return false;
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $context = array_merge($this->env->getGlobals(), $context);

        // line 1
        $this->env->loadTemplate("header.html")->display($context);
        // line 2
        echo "
";
        // line 3
        if ($this->getContext($context, 'message')) {
            echo "<div id=\"message\">";
            echo twig_escape_filter($this->env, $this->getContext($context, 'message'), "html");
            echo "</div>";
        }
        // line 4
        if ($this->getContext($context, 'error')) {
            echo "<div id=\"error\">";
            echo twig_escape_filter($this->env, $this->getContext($context, 'error'), "html");
            echo "</div>";
        }
        // line 5
        echo "
<div id=\"main\" class=\"dashboard\">
\t<h2>Dashboard</h2>

\t<div class=\"container\">
\t\t<div class=\"bigcol\">
\t\t\t<h3 class=\"action_header\">Current Assignments</h3>
\t\t\t<ul class=\"action_list\">
\t\t\t\t";
        // line 13
        if ((($this->getContext($context, 'item_count') == 0) && ($this->getContext($context, 'project_count') == 0))) {
            // line 14
            echo "\t\t\t\t\t<li class=\"blankslate\">Welcome. <a href=\"";
            echo twig_escape_filter($this->env, $this->getContext($context, 'siteroot'), "html");
            echo "/projects\">Join a project</a> to get started proofing.</li>
\t\t\t\t";
        }
        // line 16
        echo "
\t\t\t\t";
        // line 17
        $context['_parent'] = (array) $context;
        $context['_seq'] = twig_ensure_traversable($this->getContext($context, 'items'));
        foreach ($context['_seq'] as $context['_key'] => $context['item']) {
            // line 18
            echo "\t\t\t\t<li>
\t\t\t\t\t<div class=\"right_button\"><a href=\"";
            // line 19
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'item'), "editlink", array(), "any", false), "html");
            echo " ?>\" class=\"button\">Proof</a></div>
\t\t\t\t\t<div class=\"title\"><a href=\"";
            // line 20
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'item'), "editlink", array(), "any", false), "html");
            echo "\">";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'item'), "title", array(), "any", false), "html");
            echo "</a> <span class=\"deadline";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'item'), "deadlineclass", array(), "any", false), "html");
            echo "\">Due ";
            echo twig_escape_filter($this->env, $this->getContext($context, 'deadline'), "html");
            echo "</span></div>
\t\t\t\t\t<div class=\"sub\">Project: ";
            // line 21
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'item'), "project_title", array(), "any", false), "html");
            echo "</div>
\t\t\t\t</li>
\t\t\t\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['item'], $context['_parent'], $context['loop']);
        $context = array_merge($_parent, array_intersect_key($context, $_parent));
        // line 24
        echo "
\t\t\t\t";
        // line 25
        $context['_parent'] = (array) $context;
        $context['_seq'] = twig_ensure_traversable($this->getContext($context, 'projects'));
        foreach ($context['_seq'] as $context['_key'] => $context['project']) {
            // line 26
            echo "\t\t\t\t\t";
            if ($this->getAttribute($this->getContext($context, 'project'), "available", array(), "any", false)) {
                // line 27
                echo "\t\t\t\t<li>
\t\t\t\t\t<div class=\"right_button\">
\t\t\t\t\t\t<img src=\"";
                // line 29
                echo twig_escape_filter($this->env, $this->getContext($context, 'siteroot'), "html");
                echo "/snake.gif\" class=\"spinner\" /> <span class=\"button getnewitem\" data-project-slug=\"";
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'project'), "slug", array(), "any", false), "html");
                echo "\">Get new page</span></div>

\t\t\t\t\t<div class=\"title\"><a href=\"";
                // line 31
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'project'), "link", array(), "any", false), "html");
                echo "\">";
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'project'), "title", array(), "any", false), "html");
                echo "</a></div>
\t\t\t\t\t<div class=\"sub\">By ";
                // line 32
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'project'), "author", array(), "any", false), "html");
                echo "</div>
\t\t\t\t</li>
\t\t\t\t\t";
            }
            // line 35
            echo "\t\t\t\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['project'], $context['_parent'], $context['loop']);
        $context = array_merge($_parent, array_intersect_key($context, $_parent));
        // line 36
        echo "\t\t\t\t<li></li>
\t\t\t</ul>
\t\t</div>

\t\t<div class=\"sidebar\">
\t\t\t<ul id=\"stats\">
\t\t\t\t<li><label>Score</label> <span class=\"stat\">";
        // line 42
        echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'user'), "score", array(), "any", false), "html");
        echo "</span></li>
\t\t\t\t<li><label>Proofed</label> <span class=\"stat\">";
        // line 43
        echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'user'), "proofed", array(), "any", false), "html");
        echo "</span></li>
\t\t\t\t<li><label>Proofed This Past Week</label> <span class=\"stat\">";
        // line 44
        echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'user'), "proofed_past_week", array(), "any", false), "html");
        echo "</span></li>
\t\t\t</ul>
\t\t</div>
\t</div>

\t<div id=\"lower_dash\">
\t\t<div class=\"group\">
\t\t\t<h3>Recent History</h3>
\t\t\t<ul class=\"list\">
\t\t\t\t";
        // line 53
        if (($this->getContext($context, 'history_count') == 0)) {
            // line 54
            echo "\t\t\t\t<li>Nothing so far -- you must be new here. :)</li>
\t\t\t\t";
        }
        // line 56
        echo "
\t\t\t\t";
        // line 57
        $context['_parent'] = (array) $context;
        $context['_seq'] = twig_ensure_traversable($this->getContext($context, 'history'));
        foreach ($context['_seq'] as $context['_key'] => $context['item']) {
            // line 58
            echo "\t\t\t\t<li>
\t\t\t\t\t";
            // line 59
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'item'), "date_completed", array(), "any", false), "html");
            echo ": <a href='";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'item'), "editlink", array(), "any", false), "html");
            echo "'>";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'item'), "title", array(), "any", false), "html");
            echo "</a>
\t\t\t\t\t<div class=\"history_projecttitle\">Project: ";
            // line 60
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'item'), "project_title", array(), "any", false), "html");
            echo "</div>
\t\t\t\t</li>
\t\t\t\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['item'], $context['_parent'], $context['loop']);
        $context = array_merge($_parent, array_intersect_key($context, $_parent));
        // line 63
        echo "\t\t\t</ul>
\t\t</div>

\t\t<div class=\"group\">
\t\t\t<h3>Current Projects</h3>
\t\t\t<ul class=\"list\">
\t\t\t\t";
        // line 69
        if (($this->getContext($context, 'project_count') == 0)) {
            // line 70
            echo "\t\t\t\t<li>No current projects.</li>
\t\t\t\t";
        }
        // line 72
        echo "
\t\t\t\t";
        // line 73
        $context['_parent'] = (array) $context;
        $context['_seq'] = twig_ensure_traversable($this->getContext($context, 'projects'));
        foreach ($context['_seq'] as $context['_key'] => $context['project']) {
            // line 74
            echo "\t\t\t\t<li>
\t\t\t\t\t<div class=\"percentage\">
\t\t\t\t\t\t<div class=\"percentage_container\">
\t\t\t\t\t\t\t<div class=\"percent\" style=\"width: ";
            // line 77
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'project'), "percentage", array(), "any", false), "html");
            echo "px;\"></div>
\t\t\t\t\t\t\t";
            // line 78
            if (($this->getAttribute($this->getContext($context, 'project'), "num_proofs", array(), "any", false) > 1)) {
                // line 79
                echo "\t\t\t\t\t\t\t<div class=\"percent_proofs\" style=\"width: ";
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'project'), "proof_percentage", array(), "any", false), "html");
                echo "px;\"></div>
\t\t\t\t\t\t\t";
            }
            // line 81
            echo "\t\t\t\t\t\t</div> 
\t\t\t\t\t\t<p>";
            // line 82
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'project'), "percentage", array(), "any", false), "html");
            echo "% (";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'project'), "available_pages", array(), "any", false), "html");
            echo " left)</p>
\t\t\t\t\t</div>

\t\t\t\t\t<div class=\"project_title\"><a href=\"";
            // line 85
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'project'), "link", array(), "any", false), "html");
            echo "\">";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'project'), "title", array(), "any", false), "html");
            echo "</a></div>
\t\t\t\t</li>
\t\t\t\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['project'], $context['_parent'], $context['loop']);
        $context = array_merge($_parent, array_intersect_key($context, $_parent));
        // line 88
        echo "\t\t\t</ul>
\t\t</div>

\t\t<div class=\"group leaderboard\">
\t\t\t<h3>Top Proofers</h3>
\t\t\t<ol id=\"stats\">
\t\t\t";
        // line 94
        $context['_parent'] = (array) $context;
        $context['_seq'] = twig_ensure_traversable($this->getContext($context, 'topusers'));
        foreach ($context['_seq'] as $context['_key'] => $context['user']) {
            echo " 
\t\t\t<li><label>";
            // line 95
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'user'), "username", array(), "any", false), "html");
            echo "</label> <span class=\"stat\">";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'user'), "score", array(), "any", false), "html");
            echo "</span></li>
\t\t\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['user'], $context['_parent'], $context['loop']);
        $context = array_merge($_parent, array_intersect_key($context, $_parent));
        // line 97
        echo "\t\t\t</ol>
\t\t</div>
\t</div>
</div>

";
        // line 102
        $this->env->loadTemplate("footer.html")->display($context);
    }

    public function getTemplateName()
    {
        return "dashboard.html";
    }

    public function isTraitable()
    {
        return false;
    }
}
