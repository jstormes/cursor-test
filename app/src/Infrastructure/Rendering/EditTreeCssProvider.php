<?php

declare(strict_types=1);

namespace App\Infrastructure\Rendering;

final class EditTreeCssProvider
{
    private static ?string $treeCSS = null;

    public function getTreeCSS(): string
    {
        return self::$treeCSS ??= <<<CSS
        /* Edit Tree View CSS - Interactive */
        .tree {
            overflow-x: auto;
            overflow-y: visible;
        }

        .tree ul {
            padding-top: 20px; 
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
            padding: 20px 15px 0 15px;
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

        /* Tree node styling - edit view */
        .tree li div {
            border: 1px solid #1e3a8a;
            padding: 15px 20px;
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

        /* Add icon styling */
        .tree li div .add-icon {
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: #1e3a8a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            pointer-events: auto;
            z-index: 10;
            transition: all 0.3s;
            -webkit-transition: all 0.3s;
            -moz-transition: all 0.3s;
            text-decoration: none;
        }

        .tree li div .add-icon:hover {
            background-color: #0f172a;
            transform: translateX(-50%) scale(1.1);
        }

        /* Remove icon styling */
        .tree li div .remove-icon {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 20px;
            border-radius: 5px;
            background-color: #dc3545;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            pointer-events: auto;
            z-index: 10;
            transition: all 0.3s;
            -webkit-transition: all 0.3s;
            -moz-transition: all 0.3s;
            text-decoration: none;
        }

        .tree li div .remove-icon:hover {
            background-color: #a71e2a;
            transform: translateX(-50%) scale(1.1);
        }

        /* Sort left icon styling */
        .tree li div .sort-left-icon {
            position: absolute;
            left: -12px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: #1e3a8a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            pointer-events: auto;
            z-index: 10;
            transition: all 0.3s;
            -webkit-transition: all 0.3s;
            -moz-transition: all 0.3s;
            text-decoration: none;
        }

        .tree li div .sort-left-icon:hover {
            background-color: #0f172a;
            transform: translateY(-50%) scale(1.1);
        }

        /* Sort right icon styling */
        .tree li div .sort-right-icon {
            position: absolute;
            right: -12px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: #1e3a8a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            pointer-events: auto;
            z-index: 10;
            transition: all 0.3s;
            -webkit-transition: all 0.3s;
            -moz-transition: all 0.3s;
            text-decoration: none;
        }

        .tree li div .sort-right-icon:hover {
            background-color: #0f172a;
            transform: translateY(-50%) scale(1.1);
        }

        /* Hover effects for edit tree */
        .tree li div:hover, 
        .tree li div:hover+ul li div {
            background: #1e3a8a; 
            color: #ffffff; 
            border: 1px solid #1e3a8a;
        }

        .tree li div:hover .add-icon {
            background-color: #ffffff;
            color: #1e3a8a;
        }

        .tree li div:hover .remove-icon {
            background-color: #ffffff;
            color: #dc3545;
        }

        .tree li div:hover .sort-left-icon {
            background-color: #ffffff;
            color: #1e3a8a;
        }

        .tree li div:hover .sort-right-icon {
            background-color: #ffffff;
            color: #1e3a8a;
        }

        .tree li div:hover+ul li::after, 
        .tree li div:hover+ul li::before, 
        .tree li div:hover+ul::before, 
        .tree li div:hover+ul ul::before {
            border-color: #94a0b4;
        }

        /* Special styling for invisible container nodes */
        .tree li div.tree-node-no-box {
            border: none;
            background: transparent;
            padding: 0;
            position: relative;
            display: inline-block;
        }

        .tree li div.tree-node-no-box:hover+ul li div {
            background: #ffffff !important;
            color: #1e3a8a !important;
            border: 1px solid #1e3a8a !important;
        }

        /* Interactive form elements */
        .tree li div input[type="checkbox"] {
            margin: 0 4px 0 0;
            transform: scale(1.1);
            accent-color: #1e3a8a;
            vertical-align: middle;
        }

        .tree li div button {
            margin-top: 8px;
            padding: 4px 8px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 3px;
            font-size: 11px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .tree li div button:hover {
            background-color: #5a6268;
        }

        /* Responsive design for edit tree */
        @media (max-width: 768px) {
            .tree li {
                padding: 20px 12px 0 12px;
            }
            
            .tree li div {
                font-size: 10px;
                padding: 12px 15px;
            }
            
            .tree li div .add-icon,
            .tree li div .remove-icon,
            .tree li div .sort-left-icon,
            .tree li div .sort-right-icon {
                width: 18px;
                height: 18px;
                font-size: 11px;
            }
            
            .tree li div .add-icon {
                bottom: -10px;
            }
            
            .tree li div .remove-icon {
                top: -10px;
            }
            
            .tree li div .sort-left-icon {
                left: -10px;
            }
            
            .tree li div .sort-right-icon {
                right: -10px;
            }
        }
        CSS;
    }
}
