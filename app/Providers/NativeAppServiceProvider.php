<?php

namespace App\Providers;

use Native\Desktop\Facades\Window;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Contracts\ProvidesPhpIni;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    public function boot(): void
    {
        Menu::create(
            Menu::app(),
            
            Menu::make(
                Menu::label('Open Repository...')->hotkey('CmdOrCtrl+O')->event('menu:file:open-repo'),
                Menu::label('Recent Repositories'),
                Menu::separator(),
                Menu::label('Settings')->hotkey('CmdOrCtrl+,')->event('menu:file:settings'),
                Menu::separator(),
                Menu::label('Quit')->hotkey('CmdOrCtrl+Q')->event('menu:file:quit'),
            )->label('File'),
            
            Menu::make(
                Menu::label('Commit')->hotkey('CmdOrCtrl+Return')->event('menu:git:commit'),
                Menu::separator(),
                Menu::label('Push')->hotkey('CmdOrCtrl+P')->event('menu:git:push'),
                Menu::label('Pull')->hotkey('CmdOrCtrl+Shift+P')->event('menu:git:pull'),
                Menu::label('Fetch')->hotkey('CmdOrCtrl+T')->event('menu:git:fetch'),
                Menu::separator(),
                Menu::label('Stash')->event('menu:git:stash'),
            )->label('Git'),
            
            Menu::make(
                Menu::label('Switch Branch...')->hotkey('CmdOrCtrl+B')->event('menu:branch:switch'),
                Menu::label('Create Branch...')->hotkey('CmdOrCtrl+Shift+B')->event('menu:branch:create'),
                Menu::separator(),
                Menu::label('Delete Branch...')->event('menu:branch:delete'),
                Menu::label('Merge Branch...')->event('menu:branch:merge'),
            )->label('Branch'),
            
            Menu::make(
                Menu::label('About Gitty')->event('menu:help:about'),
            )->label('Help'),
        );

        Window::open()
            ->title('Gitty')
            ->width(1200)
            ->height(800)
            ->minWidth(900)
            ->minHeight(600);
    }

    public function phpIni(): array
    {
        return [];
    }
}
