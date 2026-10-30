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

namespace Customize\Controller;

use Customize\Form\Type\BusinessQuoteType;
use Customize\Service\BusinessQuotePdf;
use Customize\Service\BusinessQuoteService;
use Customize\Service\QuoteSettings;
use Eccube\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BusinessQuoteController extends AbstractController
{
    public function __construct(
        private readonly BusinessQuoteService $quotes,
        private readonly BusinessQuotePdf $pdf,
        private readonly QuoteSettings $settings,
    ) {
    }

    #[Route('/business/quote', name: 'business_quote', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $this->guard();
        $form = $this->createForm(BusinessQuoteType::class, ['quantity' => 10]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // A session-scoped cooldown also prevents rapid accidental double submissions.
            $last = $request->getSession()->get('business_quote_last', 0);
            if (time() - $last < 10) {
                $form->addError(new FormError('連続した依頼です。10秒ほど待ってから再度お試しください。'));
            } else {
                try {
                    $quote = $this->quotes->create($form->getData());
                    $owned = $request->getSession()->get('business_quote_ids', []);
                    $owned[] = $quote->getId();
                    $request->getSession()->set('business_quote_ids', array_slice($owned, -100));
                    $request->getSession()->set('business_quote_last', time());

                    return $this->redirectToRoute('business_quote_show', ['id' => $quote->getId()]);
                } catch (\InvalidArgumentException $e) {
                    $form->addError(new FormError($e->getMessage()));
                }
            }
        }

        return $this->privateResponse($this->render('BusinessQuote/form.twig', ['form' => $form->createView()]));
    }

    #[Route('/business/quote/estimate', name: 'business_quote_estimate', methods: ['GET'])]
    public function estimate(Request $request): Response
    {
        $this->guard();
        $id = $request->query->get('product', '');
        $quantity = $request->query->get('quantity', '');
        if (!ctype_digit($id) || !ctype_digit($quantity) || strlen($quantity) > 5) {
            return $this->json(['error' => '商品と1〜10,000の整数を指定してください。'], 400);
        }
        try {
            return $this->json($this->quotes->estimate((int) $id, (int) $quantity));
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/business/quote/{id}', name: 'business_quote_show', requirements: ['id' => '[a-f0-9]{32}'], methods: ['GET'])]
    #[Route('/business/quote/{id}/pdf', name: 'business_quote_pdf', requirements: ['id' => '[a-f0-9]{32}'], defaults: ['download' => true], methods: ['GET'])]
    public function show(Request $request, string $id, bool $download = false): Response
    {
        $this->guard();
        if (!in_array($id, $request->getSession()->get('business_quote_ids', []), true)) {
            throw $this->createNotFoundException();
        }
        $quote = $this->quotes->find($id);
        if (!$quote) {
            throw $this->createNotFoundException();
        }
        if ($download) {
            return $this->privateResponse(new Response($this->pdf->generate($quote), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$quote->getSnapshot()['number'].'.pdf"',
            ]));
        }

        return $this->privateResponse($this->render('BusinessQuote/show.twig', ['quote' => $quote, 'q' => $quote->getSnapshot()]));
    }

    private function guard(): void
    {
        if (!$this->settings->enabled) {
            throw $this->createNotFoundException();
        }
    }

    private function privateResponse(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
