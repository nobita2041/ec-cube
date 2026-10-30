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

namespace Customize\Controller\Admin;

use Customize\Service\BusinessQuotePdf;
use Customize\Service\BusinessQuoteService;
use Customize\Service\QuoteSettings;
use Eccube\Controller\AbstractController;
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

    #[Route('/%eccube_admin_route%/business/quotes', name: 'admin_business_quotes', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->guard();
        $page = max(1, min(100000, $request->query->getInt('page', 1)));

        return $this->privateResponse($this->render('@admin/BusinessQuote/list.twig', ['quotes' => $this->quotes->recent($page), 'page' => $page]));
    }

    #[Route('/%eccube_admin_route%/business/quotes/{id}', name: 'admin_business_quote_show', requirements: ['id' => '[a-f0-9]{32}'], methods: ['GET'])]
    #[Route('/%eccube_admin_route%/business/quotes/{id}/pdf', name: 'admin_business_quote_pdf', requirements: ['id' => '[a-f0-9]{32}'], defaults: ['download' => true], methods: ['GET'])]
    public function show(string $id, bool $download = false): Response
    {
        $this->guard();
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

        return $this->privateResponse($this->render('@admin/BusinessQuote/show.twig', ['quote' => $quote, 'q' => $quote->getSnapshot()]));
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

        return $response;
    }
}
