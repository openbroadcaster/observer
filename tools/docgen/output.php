<?php

require_once('data.php');

/* Take a documentation tree object and convert it into an HTML file for that
specific file. Uses the class and method functions to include their templates in
the output as well. Use a separate function for creating the index page, which
still needs to know about the nav menu but doesn't show any class or method
data. */
function html_file(DocGenFile $doc_file, array $nav_tree): string
{
    $html = html_header($nav_tree);

    $file_dir         = $doc_file->dir;
    $file_name        = $doc_file->name;
    $file_description = $doc_file->description;
    ob_start();
    include('templates/file.php');
    $html .= ob_get_contents();
    ob_clean();

    $doc_class   = $doc_file->getClass();
    $html .= html_class_header($doc_class);

    $doc_methods = $doc_class->sort()->getMethods();
    foreach ($doc_methods as $doc_method) {
        $html .= html_method($doc_method);
    }

    $html .= html_class_footer($doc_class);

    ob_start();
    include('templates/footer.php');
    $html .= ob_get_contents();
    ob_clean();

    return $html;
}

function html_index(array $nav_tree): string
{
    $html = html_header($nav_tree);

    ob_start();
    include('templates/footer.php');
    $html .= ob_get_contents();
    ob_clean();

    return $html;
}

/* Take an HTML documentation page and wrap it in the appropriate headers and
everything. */
function html_page(string $page, array $nav_tree): string
{
    $html = html_header($nav_tree);

    ob_start();
    include('templates/page_header.php');
    $html .= ob_get_contents();
    ob_clean();

    $html .= $page;

    ob_start();
    include('templates/page_footer.php');
    $html .= ob_get_contents();
    ob_clean();

    ob_start();
    include('templates/footer.php');
    $html .= ob_get_contents();
    ob_clean();

    return $html;
}

/* Generate HTML section for the header. This needs a separate function to
account for CSS and JS includes. */
function html_header(array $nav_tree): string
{
    ob_start();

    $title   = "OpenBroadcaster Documentation";
    $styles  = array_diff(scandir(__DIR__ . '/style/'), ['..', '.']);
    $scripts = array_diff(scandir(__DIR__ . '/js/'), ['..', '.']);
    include('templates/header.php');

    $html = ob_get_contents();
    ob_clean();

    return $html;
}

/* Generate HTML section for the class documentation. */
function html_class_header(DocGenClass $doc_class): string
{
    ob_start();

    $class_name        = $doc_class->name;
    $class_description = $doc_class->description;
    $class_package     = $doc_class->package;
    include('templates/class_header.php');

    $html = ob_get_contents();
    ob_clean();

    return $html;
}

function html_class_footer(DocGenClass $doc_class): string
{
    ob_start();

    include('templates/class_footer.php');

    $html = ob_get_contents();
    ob_clean();

    return $html;
}

/* Generate HTML section for the method documentation. */
function html_method(DocGenMethod $doc_method): string
{
    ob_start();

    $method_name        = $doc_method->name;
    $method_description = $doc_method->description;
    $method_visibility  = $doc_method->visibility;
    $method_args        = $doc_method->args;
    $method_param       = $doc_method->param;
    $method_return      = $doc_method->return;
    $method_route       = $doc_method->route;
    $method_hidden      = $doc_method->hidden;
    include('templates/method.php');

    $html = ob_get_contents();
    ob_clean();

    return $html;
}

/* HTML section for routes graph. */
function html_routes(array $routes, array $nav_tree): string
{
    $html = html_header($nav_tree);

    ob_start();
    include('templates/page_header.php');
    $html .= ob_get_contents();
    ob_clean();

    $html .= json_encode($routes, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

    ob_start();
    include('templates/page_footer.php');
    $html .= ob_get_contents();
    ob_clean();

    ob_start();
    include('templates/footer.php');
    $html .= ob_get_contents();
    ob_clean();

    return $html;
}
