<?php

namespace App\Command;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;

class CreateUserCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this
            ->setName('app:user:create')
            ->setDescription('Creates a new user.')
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the user.')
            ->addArgument('password', InputArgument::REQUIRED, 'The password of the user.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = new User();
        $user->setUsername($input->getArgument('username'));
        $password = (new BCryptPasswordEncoder($this->getContainer()->getParameter('security_bcrypt_cost')))
            ->encodePassword($input->getArgument('password'), '');
        $user->setPassword($password);
        $user->setRoles(['ROLE_USER']);

        $em->persist($user);
        $em->flush();

        $output->writeln('User successfully generated!');
    }
}
