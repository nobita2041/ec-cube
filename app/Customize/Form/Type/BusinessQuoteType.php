<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Form\Type;

use Customize\Service\BusinessQuoteService;
use Eccube\Entity\ProductClass;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class BusinessQuoteType extends AbstractType
{
    public function __construct(private readonly BusinessQuoteService $quotes)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', ChoiceType::class, [
                'label' => '商品・規格', 'choices' => $this->quotes->choices(),
                'choice_label' => static fn (ProductClass $p): string => $p->formattedProductName(),
                'choice_value' => static fn (?ProductClass $p): string => $p ? (string) $p->getId() : '',
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => '数量', 'attr' => ['min' => 1, 'max' => 10000],
                'constraints' => [new Assert\NotBlank(), new Assert\Range(min: 1, max: 10000)],
            ]);
        foreach (['company' => '会社名', 'contact' => '担当者名'] as $key => $label) {
            $builder->add($key, TextType::class, [
                'label' => $label, 'empty_data' => '',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100), new Assert\Regex(pattern: '/^[^\x00-\x1F\x7F]*$/u')],
                'attr' => ['maxlength' => 100],
            ]);
        }
        $builder->add('email', EmailType::class, [
            'label' => 'メールアドレス', 'empty_data' => '',
            'constraints' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 254)],
            'attr' => ['maxlength' => 254],
            'help' => 'デモのためメールは送信されません。',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'business_quote';
    }
}
