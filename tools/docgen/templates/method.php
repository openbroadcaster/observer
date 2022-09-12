<?php if ($method_hidden[0] === true) { ?>
    <div class="doc-method-hidden is-hidden">
<?php } ?>

<div class="doc-method">
    <details>
        <summary>
            <div class="doc-method-name">
                <code class="doc-method-code-visibility"><?=$method_visibility?></code>&nbsp;
                <code class="doc-method-code-name"><?=$method_name?></code>
                <code>(</code>
                <code class="doc-method-code-args">
                <?php foreach ($method_args as $i => $arg) {
                    echo $arg;
                    if ($i < count($method_args) - 1) {
                        echo ", ";
                    }
                } ?>
                </code>
                <code>)</code>
            </div>
        </summary>
        <div class="doc-method-body">
            <?php if ($method_hidden[0] === true) { ?>
                <div class="doc-method-hidden-reason">
                    <?=$method_hidden[1]?>
                </div>
            <?php } ?>
            <div class="doc-method-description">
                <?php foreach ($method_description as $description) { ?>
                <p><?=$description?></p>
                <?php } ?>
            </div>
            <?php if (count($method_param) > 0) { ?>
            <div class="doc-method-param">
                <span>Parameters</span>
                <table>
                    <?php foreach ($method_param as $param) { ?>
                    <tr>
                        <td><code class="doc-method-code-args"><?=$param[0]?></code></td>
                        <td><?=$param[1]?></td>
                    </tr>
                    <?php } ?>
                </table>
            </div>
            <?php } ?>
            <?php if ($method_return != "") { ?>
            <div class="doc-method-return">
                <span>Returns</span>
                <code class="doc-method-code-args"><?=$method_return?></code>
            </div>
            <?php } ?>
            <?php if (count($method_route) > 0) { ?>
            <div class="doc-method-route">
                <span>API Route</span>
                <code class="doc-method-code-name"><?=$method_route[0]?></code>
                <code class="doc-method-code-args"><?=$method_route[1]?></code>
            </div>
            <?php } ?>
        </div>
    </details>
</div>

<?php if ($method_hidden[0] === true) { ?>
    </div>
<?php } ?>
