<?php

declare(strict_types=1);

namespace App\Infrastructure\Rendering;

final class StaticCssProvider implements CssProviderInterface
{
    private static ?string $mainCSS = null;
    private static ?string $simplePageCSS = null;
    private static ?string $errorPageCSS = null;
    private static ?string $successPageCSS = null;

    #[\Override]
    public function getMainCSS(): string
    {
        return self::$mainCSS ??= <<<CSS
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f8f9fa; }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            margin: 20px;
        }
        
        .header h1 { margin: 0 0 10px 0; font-size: 2em; }
        .description { margin: 0 0 15px 0; font-size: 1.1em; opacity: 0.9; }
        .tree-info { display: flex; justify-content: center; gap: 20px; font-size: 0.9em; opacity: 0.8; }
        
        .navigation { text-align: center; margin: 20px; }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 10px;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2); }
        
        .tree ul { padding-top: 20px; position: relative; transition: all 0.5s; }
        .tree li { float: left; text-align: center; list-style-type: none; position: relative; padding: 20px 5px 0 5px; transition: all 0.5s; }
        .tree li::before, .tree li::after { content: ''; position: absolute; top: 0; right: 50%; border-top: 1px solid #ccc; width: 50%; height: 20px; }
        .tree li::after { right: auto; left: 50%; border-left: 1px solid #ccc; }
        .tree li:only-child::after, .tree li:only-child::before { display: none; }
        .tree li:only-child { padding-top: 0; }
        .tree li:first-child::before, .tree li:last-child::after { border: 0 none; }
        .tree li:last-child::before { border-right: 1px solid #ccc; border-radius: 0 5px 0 0; }
        .tree li:first-child::after { border-radius: 5px 0 0 0; }
        .tree ul ul::before { content: ''; position: absolute; top: 0; left: 50%; border-left: 1px solid #ccc; width: 0; height: 20px; }
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
            transition: all 0.5s;
        }
        
        .tree-list { margin: 20px; }
        .tree-item { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .tree-item h3 { margin: 0 0 10px 0; }
        .tree-item a { color: #667eea; text-decoration: none; }
        .tree-item a:hover { text-decoration: underline; }
        CSS;
    }

    #[\Override]
    public function getSimplePageCSS(): string
    {
        return self::$simplePageCSS ??= <<<CSS
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
        .message { color: #666; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .btn:hover { background: #0056b3; }
        CSS;
    }

    #[\Override]
    public function getErrorPageCSS(): string
    {
        return self::$errorPageCSS ??= <<<CSS
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
        .error { color: #dc3545; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .btn:hover { background: #0056b3; }
        CSS;
    }

    #[\Override]
    public function getSuccessPageCSS(): string
    {
        return self::$successPageCSS ??= <<<CSS
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f8f9fa; }
        .success { color: #28a745; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 0 10px; }
        .btn:hover { background: #0056b3; }
        CSS;
    }
}
