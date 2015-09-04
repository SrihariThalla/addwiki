<?php

namespace Mediawiki\Bot\Commands;

use Mediawiki\Bot\Config\AppConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class Setup extends Command {

	private $appConfig;

	public function __construct( AppConfig $appConfig ) {
		parent::__construct( null );
		$this->appConfig = $appConfig;
	}

	protected function configure() {
		$this
			->setName( 'setup' )
			->setDescription( 'Sets up awb' )
			->addArgument(
				'force',
				InputArgument::OPTIONAL,
				'Force the setup? Will overwrite previous things!',
				false
			);
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		if ( !$this->appConfig->isEmpty() && !$input->getArgument( 'force' ) ) {
			$output->writeln( "Ready to go!" );

			return null;
		}

		$output->writeln( "Welcome to your first time running addwiki bot!" );

		$questionHelper = $this->getQuestionHelper();

		//Add wikis?
		$addWikiQuestion =
			new ConfirmationQuestion( 'Would you like to add a wiki api endpoint? ', false );
		while ( $questionHelper->ask( $input, $output, $addWikiQuestion ) ) {
			$question = new Question( 'Please enter a code for this wiki: ' );
			$code = $questionHelper->ask( $input, $output, $question );
			if ( $this->appConfig->has( 'wikis.' . $code ) ) {
				$question =
					new ConfirmationQuestion(
						'A wiki with that code already exists, would you like to overwrite it? ',
						false
					);
				if ( !$questionHelper->ask( $input, $output, $question ) ) {
					continue 1;
				}
			}

			$question = new Question( 'Please enter a wiki api endpoint: ' );
			$url = $questionHelper->ask( $input, $output, $question );

			$output->writeln( "$code, $url" );
			$question = new ConfirmationQuestion( 'Do these details look correct? ', false );
			if ( $questionHelper->ask( $input, $output, $question ) ) {
				$this->appConfig->set(
					'wikis.' . $code,
					array( 'url' => $url )
				);
				$output->writeln( "Written to the config!" );
			}
		}

		//Add users?
		$addUserQuestion = new ConfirmationQuestion( 'Would you like to add a user? ', false );
		while ( $questionHelper->ask( $input, $output, $addUserQuestion ) ) {
			$question = new Question( 'Please enter a username: ' );
			$username = $questionHelper->ask( $input, $output, $question );

			//TODO oauth? :D
			$question = new Question( 'Please enter a password: ' );
			$question->setHidden( true );
			$password = $questionHelper->ask( $input, $output, $question );

			$question = new ConfirmationQuestion( 'Do you want to save this user? ', false );
			if ( $questionHelper->ask( $input, $output, $question ) ) {
				$this->appConfig->set(
					'users.' . $username,
					array( 'username' => $username, 'password' => $password )
				);
				$output->writeln( "Written to the config!" );
			}
		}


		$output->writeln( "Setup completed!" );
	}

	/**
	 * @return QuestionHelper
	 */
	private function getQuestionHelper() {
		return $this->getHelper( 'question' );
	}
}