<?php

declare(strict_types=1);

namespace App\Infrastructure\Rendering;

final class StandardTreeCssProvider
{
    private static ?string $treeCSS = null;

    public function getTreeCSS(): string
    {
        return self::$treeCSS ??= <<<CSS
        /* Standard Tree View CSS - Read Only */
        .tree {
            overflow-x: auto;
            overflow-y: visible;
        }

        .tree ul {
            padding-top: 20px; 
            padding-inline-start: 10px;
            position: relative;
            display: flex;
            flex-wrap: nowrap;
            transition: all 0.5s;
            -webkit-transition: all 0.5s;
            -moz-transition: all 0.5s;
        }

        .tree li {
            flex-shrink: 0;
            text-align: center;
            list-style-type: none;
            position: relative;
            padding: 20px 5px 0 5px;
            transition: all 0.5s;
            -webkit-transition: all 0.5s;
            -moz-transition: all 0.5s;
        }

        /* Tree connecting lines */
        .tree li::before, .tree li::after {
            content: '';
            position: absolute; 
            top: 0; 
            right: 50%;
            border-top: 1px solid #ccc;
            width: 50%; 
            height: 20px;
        }

        .tree li::after {
            right: auto; 
            left: 50%;
            border-left: 1px solid #ccc;
        }

        .tree li:only-child::after, .tree li:only-child::before {
            display: none;
        }

        .tree li:only-child { 
            padding-top: 0;
        }

        .tree li:first-child::before, .tree li:last-child::after {
            border: 0 none;
        }

        .tree li:last-child::before {
            border-right: 1px solid #ccc;
            border-radius: 0 5px 0 0;
            -webkit-border-radius: 0 5px 0 0;
            -moz-border-radius: 0 5px 0 0;
        }

        .tree li:first-child::after {
            border-radius: 5px 0 0 0;
            -webkit-border-radius: 5px 0 0 0;
            -moz-border-radius: 5px 0 0 0;
        }

        .tree ul ul::before {
            content: '';
            position: absolute; 
            top: 0; 
            left: 50%;
            border-left: 1px solid #ccc;
            width: 0; 
            height: 20px;
        }

        /* Tree node styling - standard view */
        .tree li div {
            border: 1px solid #1e3a8a;
            padding: 15px 10px;
            color: #1e3a8a;
            background-color: #ffffff;
            font-family: arial, verdana, tahoma;
            font-size: 11px;
            display: inline-block;
            position: relative;
            border-radius: 5px;
            -webkit-border-radius: 5px;
            -moz-border-radius: 5px;
            transition: all 0.5s;
            -webkit-transition: all 0.5s;
            -moz-transition: all 0.5s;
        }

        /* Hover effects for standard tree */
        .tree li div:hover, 
        .tree li div:hover+ul li div {
            background: #1e3a8a; 
            color: #ffffff; 
            border: 1px solid #1e3a8a;
        }

        .tree li div:hover+ul li::after, 
        .tree li div:hover+ul li::before, 
        .tree li div:hover+ul::before, 
        .tree li div:hover+ul ul::before {
            border-color: #94a0b4;
        }

        /* Responsive design for standard tree */
        @media (max-width: 768px) {
            .tree li div {
                font-size: 10px;
                padding: 12px 8px;
            }
        }
        CSS;
    }
}
