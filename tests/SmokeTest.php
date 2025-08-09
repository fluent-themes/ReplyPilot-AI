<?php
use PHPUnit\Framework\TestCase;
use App\Services\OpenAIHandler;
use App\Services\OpenAIHandlerMock;
use App\Support\Mailer;
use App\Support\MailerMock;
use App\Support\Logger;
use App\Repository\SubmissionRepository;
use App\Repository\SubmissionRepositoryMock;
use App\Core\Env;

class SmokeTest extends TestCase
{
    public function test_mock_flow(): void
    {
        $prompt = OpenAIHandler::buildSmartPrompt('Hi', 'friendly', 'Prod');
        $aiClass = Env::get('OPENAI_API_KEY') === 'MOCK_MODE' ? OpenAIHandlerMock::class : OpenAIHandler::class;
        $ai = $aiClass::query($prompt);
        $this->assertNotEmpty($ai['reply']);

        $repo = Env::get('DB_CONNECTION') === 'none'
            ? new SubmissionRepositoryMock(null)
            : new SubmissionRepository($GLOBALS['container']['db']);
        $repo->save(['dummy' => true]);

        $mailerClass = Env::get('MAIL_TRANSPORT') === 'file' ? MailerMock::class : Mailer::class;
        $mailer = new $mailerClass();
        $mailer->send('test@example.com', 'Subj', 'Body');
        $mails = glob(__DIR__ . '/../storage/mail/*.eml');
        $this->assertNotEmpty($mails);

        $logger = Logger::create();
        $logger->info('test');
        $this->assertFileExists(__DIR__ . '/../storage/logs/app.log');
    }
}
