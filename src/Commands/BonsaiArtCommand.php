<?php

namespace Jackalopelabs\BonsaiCli\Commands;

use Illuminate\Console\Command;
use Jackalopelabs\BonsaiCli\Generators\TreeGenerator;

class BonsaiArtCommand extends Command
{
    protected $signature = 'bonsai:art
                          {--style= : Tree style (formal, informal, slanting, cascade)}
                          {--season= : Season to display (spring, summer, fall, winter)}
                          {--live : Watch the tree grow in real-time}
                          {--seed= : Specific seed for consistent generation}';

    protected $description = 'Display a beautiful ASCII bonsai tree';

    protected $styles = [
        'formal' => '🎋 Formal Upright (Chokkan)',
        'informal' => '🌳 Informal Upright (Moyogi)',
        'slanting' => '🌲 Slanting (Shakan)',
        'cascade' => '🌿 Cascade (Kengai)',
    ];

    protected $messages = [
        '禅 Finding peace in simplicity...',
        '道 The way of the bonsai...',
        '木 A tree grows in harmony...',
        '心 Cultivating mindfulness...',
        '自然 Nature finds its way...',
    ];

    public function handle()
    {
        $generator = new TreeGenerator();
        
        // If live mode is enabled, show growth animation
        if ($this->option('live')) {
            return $this->showLiveGrowth($generator);
        }

        // Get options or randomize
        $style = $this->option('style') ?? array_rand($this->styles);
        $season = $this->option('season') ?? $generator->getCurrentSeason();
        $seed = $this->option('seed') ?? random_int(1, 999999);

        // Show a zen message
        $this->line("\n" . $this->messages[array_rand($this->messages)] . "\n");

        // Generate and display the tree
        $tree = $generator->generate([
            'style' => $style,
            'season' => $season,
            'seed' => $seed,
        ]);

        // Display style info
        $this->info($this->styles[$style]);
        $this->line($tree->render());
        
        // Show generation details in verbose mode
        if ($this->option('verbose')) {
            $this->table(
                ['Property', 'Value'],
                [
                    ['Style', $style],
                    ['Season', $season],
                    ['Seed', $seed],
                ]
            );
        }

        return 0;
    }

    protected function showLiveGrowth(TreeGenerator $generator)
    {
        $this->info("\n🌱 Watch your bonsai grow...\n");
        
        $stages = ['young', 'mature', 'ancient'];
        $style = $this->option('style') ?? 'formal';
        $season = $this->option('season') ?? $generator->getCurrentSeason();
        
        foreach ($stages as $stage) {
            $tree = $generator->generate([
                'style' => $style,
                'season' => $season,
                'age' => $stage,
            ]);

            // Clear previous output in terminal
            $this->output->write(sprintf("\033\143"));
            
            $this->line($tree->render());
            $this->info("\nStage: " . ucfirst($stage));
            
            // Pause between stages
            sleep(2);
        }

        $this->info("\n🎋 Your bonsai has reached maturity!\n");
        return 0;
    }
} 