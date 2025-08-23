<?php

declare(strict_types=1);

namespace App\Infrastructure\Rendering;

final class StaticCssProvider implements CssProviderInterface
{
    private static ?string $mainCSS = null;
    private static ?string $simplePageCSS = null;
    private static ?string $errorPageCSS = null;
    private static ?string $successPageCSS = null;
    
    private StandardTreeCssProvider $standardTreeCss;
    private EditTreeCssProvider $editTreeCss;

    public function __construct()
    {
        $this->standardTreeCss = new StandardTreeCssProvider();
        $this->editTreeCss = new EditTreeCssProvider();
    }

    #[\Override]
    public function getMainCSS(): string
    {
        return self::$mainCSS ??= <<<CSS
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background: #f8f9fa; 
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            margin: 20px;
        }
        
        .header h1 { 
            margin: 0 0 10px 0; 
            font-size: 2em; 
        }
        
        .description { 
            margin: 0 0 15px 0; 
            font-size: 1.1em; 
            opacity: 0.9; 
        }
        
        .tree-info { 
            display: flex; 
            justify-content: center; 
            gap: 20px; 
            font-size: 0.9em; 
            opacity: 0.8; 
        }
        
        .navigation { 
            text-align: center; 
            margin: 20px; 
        }
        
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
        
        .btn-primary { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
        }
        
        .btn-secondary { 
            background: #6c757d; 
            color: white; 
        }
        
        .btn:hover { 
            transform: translateY(-1px); 
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2); 
        }
        
        .tree-list { 
            margin: 20px; 
        }
        
        .tree-item { 
            background: white; 
            padding: 20px; 
            margin: 10px 0; 
            border-radius: 5px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
        }
        
        .tree-item h3 { 
            margin: 0 0 10px 0; 
        }
        
        .tree-item a { 
            color: #667eea; 
            text-decoration: none; 
        }
        
        .tree-item a:hover { 
            text-decoration: underline; 
        }

        /* Form styling */
        .form-container {
            margin: 20px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .tree-info {
                flex-direction: column;
                gap: 10px;
            }
            
            .header h1 {
                font-size: 1.5em;
            }
            
            .header {
                margin: 10px;
                padding: 15px;
            }
            
            .navigation {
                margin: 10px;
            }
            
            .btn {
                margin: 5px;
                padding: 8px 16px;
            }
        }
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

    #[\Override]
    public function getTreeCSS(string $treeViewType = 'standard'): string
    {
        return match ($treeViewType) {
            'edit' => $this->editTreeCss->getTreeCSS(),
            'standard' => $this->standardTreeCss->getTreeCSS(),
            default => $this->standardTreeCss->getTreeCSS(),
        };
    }
}
