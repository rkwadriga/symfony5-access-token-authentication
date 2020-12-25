<?php declare(strict_types=1);

namespace App\Form;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractForm
{
    protected ValidatorInterface $validator;
    protected ManagerRegistry $doctrine;

    public function __construct(ValidatorInterface $validator, ManagerRegistry $doctrine)
    {
        $this->validator = $validator;
        $this->doctrine = $doctrine;
    }
}