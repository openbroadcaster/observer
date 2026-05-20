<?php

// Copyright 2012-2026 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Generates routes for controllers automatically.
 *
 * @package Support
 */
namespace OpenBroadcaster\Support;

use OpenBroadcaster\Routes\{RouteFile, RouteClass, RouteMethod};

class Routes
{
    private string $targetJson;
    private array $sourceDirs;

    public function __construct()
    {
        $this->targetJson = OB_CACHE . '/routes.json';
        $this->sourceDirs = [
            OB_LOCAL . '/core/controllers/',
            OB_LOCAL . '/core/models/',
            ...glob(OB_LOCAL . '/modules/*/controllers/'),
            ...glob(OB_LOCAL . '/modules/*/models/'),
        ];
    }

    public function needsUpdate(): bool
    {
        if (! file_exists($this->targetJson)) {
            return true;
        }

        $json = json_decode(file_get_contents($this->targetJson), true);
        $updated = $json['updated'] ?? null;

        if (! $updated) {
            return true;
        }

        foreach ($this->sourceDirs as $dir) {
            if (! str_ends_with($dir, '/controllers/')) {
                continue;
            }

            foreach (new \FilesystemIterator($dir) as $file) {
                if ($file->isDir()) {
                    continue;
                }

                if ($file->getExtension() !== 'php') {
                    continue;
                }

                if (! $file->isReadable()) {
                    error_log("[W] File '" . $file->getFilename() . "' isn't readable. Ignoring.\n");
                    continue;
                }

                if (! ($updated[$file->getRealPath()] ?? null) || $updated[$file->getRealPath()] !== $file->getMTime()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function genRoutes(): bool
    {
        $docFiles = [];
        $updated = [];
        foreach ($this->sourceDirs as $dir) {
            if (! str_ends_with($dir, '/controllers/')) {
                continue;
            }

            foreach (new \FilesystemIterator($dir) as $file) {
                if ($file->isDir()) {
                    continue;
                }

                if ($file->getExtension() !== 'php') {
                    continue;
                }

                if (! $file->isReadable()) {
                    error_log("[W] File '" . $file->getFilename() . "' isn't readable. Ignoring.\n");
                    continue;
                }

                $content = $this->parse_clean(file_get_contents($file->getPathname()));
                $blocks = $this->parse_blocks($content);
                $updated[$file->getRealPath()] = $file->getMTime();

                $docFiles[] = $this->generate_tree($blocks, $file->getFilename(), $dir);
            }
        }

        $routes = [];
        foreach ($docFiles as $file) {
            foreach ($file->getClass()->getMethods() as $method) {
                if (count($method->routes) > 0) {
                    foreach ($method->routes as $route) {
                        // Each route is stored in JSON as an array containing elements in the format
                        // [
                        //      /api/v2/route/string,
                        //      controller,
                        //      method
                        // ]
                        //
                        // When a route comes from a module, it is instead stored as
                        // [
                        //      /api/v2/module/ModuleName/route/string,
                        //      {
                        //          'module': module,
                        //          'controller': controller
                        //      },
                        //      method
                        // ]
                        $module = $file->getClass()->module;
                        $controller = $file->getClass()->name;

                        $routes[$route[0]][] = [
                            $route[1],
                            $module ? ['module' => $module, 'controller' => $controller] : $controller,
                            $method->name
                        ];
                    }
                }
            }
        }

        if ($this->routes_contain_duplicates($routes)) {
            error_log("[E] Duplicate routes found. Quitting." . PHP_EOL);
            return false;
        }

        $json = json_encode([
            'updated' => $updated,
            'routes' => $routes,
        ], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

        if (file_put_contents($this->targetJson, $json) === false) {
            error_log("[E] Failed to write routes to file: {$this->targetJson}." . PHP_EOL);
            return false;
        }

        return true;
    }

    public function genDocs(string $targetDir): bool
    {
        foreach ($this->sourceDirs as $dir) {
            if (! is_readable($dir)) {
                echo "[E] Source directory isn't readable: " . $dir . "\n";
                return false;
            }
        }

        /* Iterate over source directories and their files (don't do so recursively,
        the OpenBroadcaster framework doesn't support this for the core files anyway,
        so each directory with source code has to be added to the source directories
        array). Make sure all files are parsed before outputting any HTML, as we need
        to know about all the files to be able to output the navigation sidebar. */
        echo "Parsing PHP files and generating documentation structure.\n";
        $pages = [];
        $doc_files = [];
        foreach ($this->sourceDirs as $dir) {
            foreach (new \FilesystemIterator($dir) as $file) {
                if ($file->isDir()) {
                    continue;
                }

                if (! $file->isReadable()) {
                    echo "[W] File '" . $file->getFilename() . "' isn't readable. Ignoring.\n";
                    continue;
                }

                /* Check if it's a HTML file, and if so, save it as one of the general
                documentation files to be inserted later. */
                if ($file->getExtension() == 'html') {
                    $pages[$file->getFilename()] = file_get_contents($file->getPathname());

                    continue;
                }

                /* Otherwise, we're assuming it's a PHP file with DocGen strings, and we use
                our custom parsing functions. */
                $content = $this->parse_clean(file_get_contents($file->getPathname()));
                $blocks = $this->parse_blocks($content);

                if (! str_ends_with(rtrim($dir, '/'), '/core/controllers')) {
                    $moduleStr = '/modules/' . basename(dirname($dir)) . '/controllers';
                } else {
                    $moduleStr = '/core/controllers';
                }

                $doc_files[] = $this->generate_tree($blocks, $file->getFilename(), $moduleStr);
            }
        }

        /* Generate routes as part of the documentation. */
        $routes = [];
        foreach ($doc_files as $file) {
            foreach ($file->getClass()->getMethods() as $method) {
                if (count($method->routes) > 0) {
                    foreach ($method->routes as $route) {
                        $routes[$route[0]][] = [$route[1], strtolower($file->getClass()->name), $method->name];
                    }
                }
            }
        }

        if ($this->routes_contain_duplicates($routes)) {
            echo "[E] Duplicate routes found. Quitting.\n";
            return false;
        }

        echo "Generating navigation tree for HTML output.\n";
        $nav_tree = [];
        foreach ($pages as $index => $page) {
            $nav_tree['pages'][] = explode(".", $index)[0];
        }
        foreach ($doc_files as $doc_file) {
            $nav_tree[$doc_file->getClass()->package][] =
                ($doc_file->getClass()->name != null) ? $doc_file->getClass()->name : $doc_file->name;
        }
        foreach ($nav_tree as &$tree) {
            sort($tree);
        }

        echo "Outputting documentation as HTML.\n";
        foreach ($pages as $index => $page) {
            $doc_file_path = $targetDir . "/pages." . $index;
            file_put_contents($doc_file_path, $this->html_page($page, $nav_tree));
        }
        foreach ($doc_files as $doc_file) {
            $doc_file_path = $targetDir . "/" . $doc_file->getClass()->package . "."
            . (($doc_file->getClass()->name != null) ? $doc_file->getClass()->name : $doc_file->name)
            . ".html";
            file_put_contents($doc_file_path, $this->html_file($doc_file, $nav_tree));
        }

        echo "Outputting route graph HTML.\n";
        file_put_contents($targetDir . "/routes.html", $this->html_routes($this->trim_toplevel_routes($this->routes_by_endpoint($routes)), $nav_tree));
        file_put_contents($targetDir . "/index.html", $this->html_index($nav_tree));
        echo "Successfully generated documentation HTML.\n\n";

        /* Copy style files and other general data needed for the documentation
        to function. */
        echo "Copying style and script files over to target directory.\n";
        mkdir($targetDir . "/style");
        foreach (new \FilesystemIterator(OB_LOCAL . '/core/data/routes/style/') as $style) {
            if ($style->isDir()) {
                continue;
            }

            if (! $style->isReadable()) {
                echo "[W] Stylesheet '" . $style->getFilename() . "' isn't readable. Ignoring.\n";
                continue;
            }

            copy($style->getPathname(), $targetDir . "/style/" . $style->getFilename());
        }

        mkdir($targetDir . "/js");
        foreach (new \FilesystemIterator(OB_LOCAL . '/core/data/routes/js/') as $script) {
            if ($script->isDir()) {
                continue;
            }

            if (! $script->isReadable()) {
                echo "[W] Javascript file '" . $script->getFilename() . "' isn't readable. Ignoring.\n";
                continue;
            }

            copy($script->getPathname(), $targetDir . "/js/" . $script->getFilename());
        }
        echo "Successfully copied style and script files.\n\n";

        echo "Successfully generated documentation. Statistics:\n";
        echo "Packages:\t" . count($nav_tree) . "\n";
        echo "Classes:\t" . count($doc_files) . "\n";
        echo "CSS Files:\t" . (count(scandir($targetDir . "/style/")) - 2) . "\n";
        echo "JS Files:\t" . (count(scandir($targetDir . "/js/")) - 2) . "\n\n";

        return true;
    }

    /* Parsing functions. The first thing we need to do is clean the content we
    get from PHP files a bit. This includes removing empty lines (just in case there's
    gaps between DocBlocks and start of class/method definitions), trimming all the
    whitespace, and splitting the lines into an array. */
    private function parse_clean(string $content): array
    {
        $result = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $content);
        $result = preg_split("/\r\n|\n|\r/", $result);
        foreach ($result as $i => $line) {
            $result[$i] = htmlspecialchars(trim($line));
        }

        return $result;
    }

    /* Convert the array of strings into an array containing all the start- and
    endpoints of DocBlocks, as well as the line after the DocBlock containing the
    class or method declaration. Note that since we've stripped empty lines, these
    indices do *not* correspond to lines in the code. The characters at the end of
    a DocBlock can also occur at the end of a regular multi-line comment, which is
    why we're checking specifically for the next occurence of these characters after
    the start of a DocBlock (which necessarily ends it). */
    private function parse_blocks(array $content): array
    {
        $blocks = array();

        // Find the start of each DocBlock.
        $doc_start = array_keys($content, "/**");
        foreach ($doc_start as $start) {
            // Find the end of the current DocBlock as a key in the content array.
            $end = array_values(array_filter(array_keys($content, "*/"), function ($value) use ($start) {
                return ($value > $start);
            }));

            if (empty($end)) {
                exit("[E] Parsing error: could not find ending */ for DocBlock starting with /**.");
            }

            if (!isset($content[$end[0] + 1])) {
                exit("[E] Parsing error: Method or class declaration could not be found under DocBlock.");
            }

            // Grab the doc between start and end of the block.
            $doc = array_slice($content, $start + 1, $end[0] - $start - 1);
            foreach ($doc as $i => $line) {
                $doc[$i] = ltrim(ltrim($line, '*'));
            }

            // The declaration of DocBlock type might not be the first line after the end. Filter out lines that
            // may contain some extraneous nonsense like `namespace` or `use`. Make sure not to keep going if none
            // of the values to skip over are found.
            $contentBetween = array_filter($content, fn ($key) => $key > $end[0], ARRAY_FILTER_USE_KEY);
            $n = 1;
            foreach ($contentBetween as $possibleDecl) {
                $skipValues = [
                    'namespace',
                    'use',
                ];

                $found = false;
                foreach ($skipValues as $skipValue) {
                    if (str_starts_with($possibleDecl, $skipValue . ' ')) {
                        $found = true;
                        $n++;

                        break;
                    }
                }

                if (! $found) {
                    break;
                }
            }

            $blocks[] = [
                'doc'   => $doc,
                'decl'  => $content[$end[0] + $n]
            ];
        }

        return $blocks;
    }

    /* Get the declaration type under the DocBlock. Using some simplifying assumptions,
    we can check for the following strings: if it contains 'function', it's a class method;
    if it contains 'class', it's a class (throwing an error if we find more than one in
    a single file); otherwise, we assume it's a DocBlock description of the current file.
    We return the type of declaration in an array with some additional information where
    relevant. */
    private function parse_decl(string $decl): array
    {
        if (strpos($decl, "function") !== false) {
            $args = explode(",", trim(substr($decl, strpos($decl, "(") + 1, strpos($decl, ")") - strpos($decl, "(") - 1)));
            foreach ($args as $i => $arg) {
                $args[$i] = ltrim(trim($arg), "$");
            }

            return [
                'type'       => 'method',
                'visibility' => (strpos($decl, "private") !== false) ? "private" : ((strpos($decl, "protected") !== false) ? "protected" : "public"),
                'name'       => trim(explode("function", substr($decl, 0, strpos($decl, "(")))[1]),
                'args'       => $args
            ];
        } elseif (strpos($decl, "class") !== false) {
            return [
                'type'       => 'class',
                'name'       => explode(" ", trim(explode("class", $decl)[1]))[0]
            ];
        } else {
            return [
                'type'       => 'file'
            ];
        }
    }

    /* Parse the actual DocBlock. We're recognizing a small number of tags, which
    can be used later when generating the HTML documentation. Note that parsing the
    DocBlock is tag-unaware, which means that it may parse tags that never show up
    in the actual documentation. */
    private function parse_doc(array $lines): array
    {
        $doc = [
            'description' => [],
            'tags'        => []
        ];

        $new_p = true;
        foreach ($lines as $line) {
            if ($line == "") {
                $new_p = true;
                continue;
            }

            if ($line[0] == '@') {
                $doc['tags'][] = [substr($line, 1, strpos($line, " ") - 1), substr($line, strpos($line, " ") + 1)];
            } else {
                if ($new_p) {
                    $doc['description'][] = $line;
                    $new_p = false;
                } else {
                    $doc['description'][count($doc['description']) - 1] .= " " . $line;
                }
            }
        }

        return $doc;
    }

    /* Generate the object tree we'll need to generate the actual HTML documentation.
    Using previously determined blocks (arrays of decls and docs), and the name of the
    file, figure out all the classes and methods and their documentation. */
    private function generate_tree(array $blocks, string $filename, string $dir): RouteFile
    {
        $doc_file    = new RouteFile($filename, $dir);
        $doc_class   = null;
        $doc_methods = [];

        foreach ($blocks as $block) {
            $decl = $this->parse_decl($block['decl']);
            $doc  = $this->parse_doc($block['doc']);

            switch ($decl['type']) {
                case 'file':
                    $doc_file->description = array_merge($doc_file->description, $doc['description']);
                    break;
                case 'class':
                    if ($doc_class != null) {
                        error_log("[W] Multiple class definitions are not allowed in a single file: " . $doc_class->name . "\n");
                        continue 2;
                    }

                    $doc_class = new RouteClass($decl['name'], $doc['description']);

                    foreach ($doc['tags'] as $tag) {
                        switch ($tag[0]) {
                            case 'package':
                                $doc_class->package = $tag[1];
                                break;
                            default:
                                error_log("[W] Unsupported tag found: @" . $tag[0] . " (" . $tag[1] . ")\n");
                                break;
                        }
                    }

                    if (! str_ends_with(rtrim($dir, '/'), '/core/controllers')) {
                        $moduleStr = basename(dirname($dir));
                        $doc_class->module = $moduleStr;
                    }
                    break;
                case 'method':
                    $method = new RouteMethod($decl['name'], $doc['description'], $decl['visibility'], $decl['args']);

                    foreach ($doc['tags'] as $tag) {
                        switch ($tag[0]) {
                            case 'param':
                                if (strpos($tag[1], " ") !== false) {
                                    $param = substr($tag[1], 0, strpos($tag[1], " "));
                                    $desc  = substr($tag[1], strpos($tag[1], " ") + 1);
                                    $method->param[] = [$param, $desc];
                                } else {
                                    $method->param[] = [$tag[1], ""];
                                }
                                break;
                            case 'return':
                                $method->return = $tag[1];
                                break;
                            case 'route':
                                $route_method = substr($tag[1], 0, strpos($tag[1], " "));
                                $route_url = substr($tag[1], strpos($tag[1], " ") + 1);

                                if ($doc_class !== null && $doc_class->module !== null) {
                                    $route_url = '/api/v2/module/' . $doc_class->module . '/' . trim($route_url, '/');
                                } else {
                                    // TODO: /v2/ is currently manually part of route strings; can update to remove from all
                                    // routes and just add in here, probably? Currently inconsistent with modules above.
                                    $route_url = '/api/' . trim($route_url, '/');
                                }

                                if (!in_array($route_method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
                                    error_log('[E] Unsupported HTTP method used in @route tag: ' . $tag[1] . ".\n");
                                    break;
                                }

                                $method->routes[] = [$route_method, $route_url];
                                break;
                            case 'hidden':
                                $method->hidden = [true, $tag[1]];
                                break;
                            default:
                                error_log("[W] Unsupported tag found: @" . $tag[0] . " (" . $tag[1] . ")\n");
                                break;
                        }
                    }

                    $doc_methods[] = $method;
                    break;
            }
        }

        if ($doc_class != null) {
            foreach ($doc_methods as $method) {
                $doc_class->addMethod($method);
            }
            $doc_file->setClass($doc_class);
        }

        return $doc_file;
    }

    // Check all routes for any duplicates, to make sure there is no ambiguity when
    // accessing a specific URL with a specific HTTP method.
    private function routes_contain_duplicates(array $routes): bool
    {
        $dupes = false;

        // Check all HTTP methods separately. Duplication is fine as long as they
        // use different methods.
        foreach ($routes as $method => $routes_for_method) {
            $route_urls = [];

            foreach ($routes_for_method as $route) {
                // Save all URLs, replacing any arguments with the _ARG_ wildcard, since
                // duplication can still happen with differently named parameters: it's the
                // number of parameters that matters.
                $route_urls[] = preg_replace("/(\(:)(.*?)(:\))/", "_ARG_", $route[0]);
            }

            if (count($route_urls) !== count(array_unique($route_urls))) {
                $dupes = true;

                error_log("[E] Duplicate routes detected for " . $method . " method!\n");
                foreach (array_diff_assoc($route_urls, array_unique($route_urls)) as $dupe_route) {
                    error_log("\t[E] " . $dupe_route . "\n");
                }
            }
        }

        return $dupes;
    }

    // Get all routes sorted by endpoints instead of by method.
    private function routes_by_endpoint(array $routes): array
    {
        $endpoints = [];
        foreach ($routes as $method => $method_routes) {
            foreach ($method_routes as $route) {
                $parts = explode('/', trim($route[0], '/'));
                $this->build_endpoint($endpoints, $parts, $method, [$route[1], $route[2]]);
            }
        }

        return $endpoints;
    }

    private function build_endpoint(&$endpoints, $parts_remaining, $method, $values)
    {
        if ($parts_remaining == []) {
            $endpoints['/'][$method] = $values;
        } else {
            $this->build_endpoint($endpoints[$parts_remaining[0]], array_slice($parts_remaining, 1), $method, $values);
        }
    }

    // Called to trim top level routes AFTER they've been sorted by endpoint using
    // routes_by_endpoint
    private function trim_toplevel_routes(array $routes): array
    {
        if (count($routes) < 1) {
            error_log('[E] Failed to trim top level routes, since routes array is empty. This ordinarily shouldn\'t happen!\n');
            return $routes;
        }

        // Topmost route only has one item, recursively call function on its member.
        if (count($routes) == 1) {
            return $this->trim_toplevel_routes(array_values($routes)[0]);
        }

        // Topmost route has multiple items, return routes.
        return $routes;
    }

    /* Take a documentation tree object and convert it into an HTML file for that
    specific file. Uses the class and method functions to include their templates in
    the output as well. Use a separate function for creating the index page, which
    still needs to know about the nav menu but doesn't show any class or method
    data. */
    private function html_file(RouteFile $doc_file, array $nav_tree): string
    {
        $html = $this->html_header($nav_tree);

        $file_dir         = $doc_file->dir;
        $file_name        = $doc_file->name;
        $file_description = $doc_file->description;
        ob_start();
        include(OB_LOCAL . '/core/data/routes/templates/file.php');
        $html .= ob_get_contents();
        ob_clean();

        $doc_class   = $doc_file->getClass();
        $html .= $this->html_class_header($doc_class);

        $doc_methods = $doc_class->sort()->getMethods();
        foreach ($doc_methods as $doc_method) {
            $html .= $this->html_method($doc_method);
        }

        $html .= $this->html_class_footer($doc_class);

        ob_start();
        include(OB_LOCAL . '/core/data/routes/templates/footer.php');
        $html .= ob_get_contents();
        ob_clean();

        return $html;
    }

    private function html_index(array $nav_tree): string
    {
        $html = $this->html_header($nav_tree);

        ob_start();
        include(OB_LOCAL . '/core/data/routes/templates/footer.php');
        $html .= ob_get_contents();
        ob_clean();

        return $html;
    }

    /* Take an HTML documentation page and wrap it in the appropriate headers and
    everything. */
    private function html_page(string $page, array $nav_tree): string
    {
        $html = $this->html_header($nav_tree);

        ob_start();
        include(OB_LOCAL . '/core/data/routes/templates/page_header.php');
        $html .= ob_get_contents();
        ob_clean();

        $html .= $page;

        ob_start();
        include(OB_LOCAL . '/core/data/routes/templates/page_footer.php');
        $html .= ob_get_contents();
        ob_clean();

        ob_start();
        include(OB_LOCAL . '/core/data/routes/templates/footer.php');
        $html .= ob_get_contents();
        ob_clean();

        return $html;
    }

    /* Generate HTML section for the header. This needs a separate function to
    account for CSS and JS includes. */
    private function html_header(array $nav_tree): string
    {
        ob_start();

        $title   = "OpenBroadcaster Documentation";
        $styles  = array_diff(scandir(OB_LOCAL . '/core/data/routes/style/'), ['..', '.']);
        $scripts = array_diff(scandir(OB_LOCAL . '/core/data/routes/js/'), ['..', '.']);
        include(OB_LOCAL . '/core/data/routes/templates/header.php');

        $html = ob_get_contents();
        ob_clean();

        return $html;
    }

    /* Generate HTML section for the class documentation. */
    private function html_class_header(RouteClass $doc_class): string
    {
        ob_start();

        $class_name        = $doc_class->name;
        $class_description = $doc_class->description;
        $class_package     = $doc_class->package;
        include(OB_LOCAL . '/core/data/routes/templates/class_header.php');

        $html = ob_get_contents();
        ob_clean();

        return $html;
    }

    private function html_class_footer(RouteClass $doc_class): string
    {
        ob_start();

        include(OB_LOCAL . '/core/data/routes/templates/class_footer.php');

        $html = ob_get_contents();
        ob_clean();

        return $html;
    }

    /* Generate HTML section for the method documentation. */
    private function html_method(RouteMethod $doc_method): string
    {
        ob_start();

        $method_name        = $doc_method->name;
        $method_description = $doc_method->description;
        $method_visibility  = $doc_method->visibility;
        $method_args        = $doc_method->args;
        $method_param       = $doc_method->param;
        $method_return      = $doc_method->return;
        $method_routes      = $doc_method->routes;
        $method_hidden      = $doc_method->hidden;
        include(OB_LOCAL . '/core/data/routes/templates/method.php');

        $html = ob_get_contents();
        ob_clean();

        return $html;
    }

    /* HTML section for routes graph. */
    private function html_routes(array $routes, array $nav_tree): string
    {
        $html = $this->html_header($nav_tree);

        ob_start();
        include(OB_LOCAL . '/core/data/routes/templates/page_header.php');
        $html .= ob_get_contents();
        ob_clean();

        ob_start();

        include(OB_LOCAL . '/core/data/routes/templates/routes.php');
        $html .= ob_get_contents();

        ob_clean();

        ob_start();
        include(OB_LOCAL . '/core/data/routes/templates/page_footer.php');
        $html .= ob_get_contents();
        ob_clean();

        ob_start();
        include(OB_LOCAL . '/core/data/routes/templates/footer.php');
        $html .= ob_get_contents();
        ob_clean();

        return $html;
    }

}
