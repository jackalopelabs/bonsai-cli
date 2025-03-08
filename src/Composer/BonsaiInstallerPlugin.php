<?php

namespace Jackalopelabs\BonsaiCli\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class BonsaiInstallerPlugin implements PluginInterface, EventSubscriberInterface
{
    protected $composer;
    protected $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // No action needed on deactivation
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        // No action needed on uninstall
    }

    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'registerServiceProvider',
            ScriptEvents::POST_UPDATE_CMD => 'registerServiceProvider',
        ];
    }

    public function registerServiceProvider(Event $event)
    {
        $io = $event->getIO();
        $io->write('<info>Registering BonsaiServiceProvider...</info>');

        $composerJsonPath = getcwd() . '/composer.json';
        
        if (!file_exists($composerJsonPath)) {
            $io->write('<error>composer.json not found in the project root.</error>');
            return;
        }

        $composerJson = json_decode(file_get_contents($composerJsonPath), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $io->write('<error>Failed to parse composer.json: ' . json_last_error_msg() . '</error>');
            return;
        }

        // Initialize extra.acorn.providers if it doesn't exist
        if (!isset($composerJson['extra'])) {
            $composerJson['extra'] = [];
        }
        if (!isset($composerJson['extra']['acorn'])) {
            $composerJson['extra']['acorn'] = [];
        }
        if (!isset($composerJson['extra']['acorn']['providers'])) {
            $composerJson['extra']['acorn']['providers'] = [];
        }

        // Add BonsaiServiceProvider if not already present
        $provider = 'Jackalopelabs\\BonsaiCli\\Providers\\BonsaiServiceProvider';
        if (!in_array($provider, $composerJson['extra']['acorn']['providers'])) {
            $composerJson['extra']['acorn']['providers'][] = $provider;
            
            file_put_contents(
                $composerJsonPath,
                json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
            );
            
            $io->write('<info>✓ Added BonsaiServiceProvider to composer.json</info>');
        } else {
            $io->write('<info>BonsaiServiceProvider already registered in composer.json</info>');
        }
    }
} 