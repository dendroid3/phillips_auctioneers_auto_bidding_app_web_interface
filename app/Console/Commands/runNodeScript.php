namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RunNodeScript extends Command
{
    protected $signature = 'run:node-script';
    protected $description = 'Run a Node.js script';

    public function handle()
    {
        $scriptPath = base_path('path/to/your/script.js');
        
        $process = new Process(['node', $scriptPath]);
        $process->run();
        
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        
        $this->info($process->getOutput());
    }
}
