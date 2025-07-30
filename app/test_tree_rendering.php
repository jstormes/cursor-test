<?php
require_once 'vendor/autoload.php';

use App\Domain\Tree\SimpleNode;
use App\Domain\Tree\ButtonNode;
use App\Domain\Tree\HtmlTreeNodeRenderer;

// Create a simple tree structure for testing
$rootNode = new SimpleNode(null, 'Root Node', 1, null, 0);
$child1 = new SimpleNode(null, 'Child 1', 1, 1, 0);
$child2 = new ButtonNode(null, 'Child 2', 1, 1, 1);
$grandchild = new SimpleNode(null, 'Grandchild', 1, 2, 0);

$rootNode->addChild($child1);
$rootNode->addChild($child2);
$child2->addChild($grandchild);

// Render the tree
$renderer = new HtmlTreeNodeRenderer();
$html = $renderer->render($rootNode);

echo "Generated HTML:\n";
echo $html . "\n\n";

echo "HTML contains 'node' class: " . (strpos($html, 'class="node"') !== false ? 'YES' : 'NO') . "\n";
echo "HTML contains 'button-node' class: " . (strpos($html, 'class="node button-node"') !== false ? 'YES' : 'NO') . "\n";
echo "HTML contains nested <ul> tags: " . (strpos($html, '<ul>') !== false ? 'YES' : 'NO') . "\n";
echo "HTML contains <li> tags: " . (strpos($html, '<li>') !== false ? 'YES' : 'NO') . "\n"; 