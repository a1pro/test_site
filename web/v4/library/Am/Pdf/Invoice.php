<?php

if (!@class_exists('Zend_Pdf_Page', true))
    include_once('Zend/Pdf_Pack.php');

class Am_Pdf_Invoice
{

    /** @var Invoice */
    protected $invoice;
    /** @var Am_Di */
    protected $di = null;
    /** @var int */
    protected $pointer;

    const PAPER_FORMAT_LETTER = Zend_Pdf_Page::SIZE_LETTER;
    const PAPPER_FORMAT_A4 = Zend_Pdf_Page::SIZE_A4;

    function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    function setDi(Am_Di $di)
    {
        $this->di = $di;
    }

    /**
     *
     * @return Am_Di
     */
    function getDi()
    {
        return $this->di ? $this->di : Am_Di::getInstance();
    }

    protected function getPaperWidth()
    {
        return $this->getDi()->config->get('invoice_format', self::PAPER_FORMAT_LETTER) == self::PAPER_FORMAT_LETTER ?
            Am_Pdf_Page_Decorator::PAGE_LETTER_WIDTH :
            Am_Pdf_Page_Decorator::PAGE_A4_WIDTH;
    }

    protected function getPaperHeight()
    {
        return $this->getDi()->config->get('invoice_format', self::PAPER_FORMAT_LETTER) == self::PAPER_FORMAT_LETTER ?
            Am_Pdf_Page_Decorator::PAGE_LETTER_HEIGHT :
            Am_Pdf_Page_Decorator::PAGE_A4_HEIGHT;
    }

    protected function drawDefaultTemplate(Zend_Pdf $pdf)
    {
        $pointer = $this->getPaperHeight() - 20;

        $page = new Am_Pdf_Page_Decorator($pdf->pages[0]);
        if (!($ic = $this->getDi()->config->get('invoice_contacts')))
        {
            $ic = $this->getDi()->config->get('site_title') . '<br>' . $this->getDi()->config->get('root_url');
        }

        $page->setFont($this->getFontRegular(), 12);

        $invoice_logo_id = $this->getDi()->config->get('invoice_logo');
        if ($invoice_logo_id && ($upload = $this->getDi()->uploadTable->load($invoice_logo_id, false)))
        {
            $image = null;

            switch ($upload->getType())
            {
                case 'image/png' :
                    $image = new Zend_Pdf_Resource_Image_Png($upload->getFullPath());
                    break;
                case 'image/jpeg' :
                    $image = new Zend_Pdf_Resource_Image_Jpeg($upload->getFullPath());
                    break;
                case 'image/tiff' :
                    $image = new Zend_Pdf_Resource_Image_Tiff($upload->getFullPath());
                    break;
            }

            if ($image)
            {
                $page->drawImage($image, 20, $pointer - 100, 220, $pointer);
            }
        }

        $page->drawTextWithFixedWidth($ic, $this->getPaperWidth() - 20, $pointer, 400, null, Am_Pdf_Page_Decorator::ALIGN_RIGHT);
        $pointer-=110;
        $page->drawLine(20, $pointer, $this->getPaperWidth() - 20, $pointer);
        $page->nl($pointer);
        $page->nl($pointer);

        return $pointer;
    }

    /**
     *
     * @return Zend_Pdf
     *
     */
    protected function createPdfTemplate()
    {
        if ($this->getDi()->config->get('invoice_custom_template') &&
            ($upload = $this->getDi()->uploadTable->load($this->getDi()->config->get('invoice_custom_template'))))
        {
            $pdf = Zend_Pdf::load($upload->getFullPath());

            $this->pointer = $this->getPaperHeight() - $this->getDi()->config->get('invoice_skip', 150);
        }
        else
        {
            $pdf = new Zend_Pdf();
            $pdf->pages[0] = $pdf->newPage($this->getDi()->config->get('invoice_format', Zend_Pdf_Page::SIZE_LETTER));

            $this->pointer = $this->drawDefaultTemplate($pdf);
        }

        return $pdf;
    }

    //can be called only after createPdfTemplate
    protected function getPointer()
    {
        return $this->pointer;
    }

    protected function getFontRegular()
    {
        return Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
    }

    protected function getFontBold()
    {
        return Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
    }

    public function getFileName()
    {
        return sprintf("amember-invoice-%s.pdf", $this->invoice->public_id);
    }

    public function render()
    {

        $invoice = $this->invoice;

        $pdf = $this->createPdfTemplate();

        $padd = 20;
        $left = $padd;
        $right = $this->getPaperWidth() - $padd;

        $fontH = $this->getFontRegular();
        $fontHB = $this->getFontBold();

        $styleBold = array(
            'font' => array(
                'face' => $fontHB,
                'size' => 12
            ));


        $page = new Am_Pdf_Page_Decorator($pdf->pages[0]);
        $page->setFont($fontH, 12);

        $pointer = $this->getPointer();


        $pointerL = $pointerR = $pointer;

        $page->drawText('Invoice Number: ' . $invoice->public_id, $left, $pointerL);
        $page->nl($pointerL);
        $page->drawText('Date: ' . amDatetime($invoice->tm_added), $left, $pointerL);
        $page->nl($pointerL);

        $page->setFont($fontHB, 12);
        $page->drawText($invoice->getName(), $right, $pointerR, null, Am_Pdf_Page_Decorator::ALIGN_RIGHT);
        $page->setFont($fontH, 12);

        $page->nl($pointerR);
        $page->drawText($invoice->getEmail(), $right, $pointerR, null, Am_Pdf_Page_Decorator::ALIGN_RIGHT);
        $page->nl($pointerR);
        $page->drawText('', $right, $pointerR, null, Am_Pdf_Page_Decorator::ALIGN_RIGHT);
        $page->nl($pointerR);
        $page->drawText(
            implode(', ',array_filter(array($invoice->getStreet(), $invoice->getCity())))
            , $right, $pointerR, null, Am_Pdf_Page_Decorator::ALIGN_RIGHT);
        $page->nl($pointerR);
        $page->drawText(
                implode(', ', array_filter(array(
                    $this->getState($invoice),
                    $invoice->getZip(),
                    $this->getCountry($invoice))))
            , $right, $pointerR, null, Am_Pdf_Page_Decorator::ALIGN_RIGHT);
        $page->nl($pointerR);

        $pointer = min($pointerR, $pointerL);
        $page->nl($pointer);


        $table = new Am_Pdf_Table();
        $table->setMargin($padd, $padd, $padd, $padd);
        $table->setStyleForRow(
            1, array(
            'shape' => array(
                'type' => Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE,
                'color' => new Zend_Pdf_Color_Html("#cccccc")
            ),
            'font' => array(
                'face' => $fontHB,
                'size' => 12
            )
            )
        );

        $table->setStyleForColumn(
            2, array(
            'align' => 'right',
            'width' => 80
            )
        );

        $table->addRow(array(
            ___('Subscription/Product Title'),
            ___('Price')
        ));

        foreach ($invoice->getItems() as $p)
        {
            $table->addRow(array(
                $p->item_title,
                $invoice->getCurrency($p->getFirstSubtotal())
            ));
        }

        $table->addRow(array(
            ___('Subtotal'),
            $invoice->getCurrency($invoice->first_subtotal)
        ))->addStyle($styleBold);

        if ($invoice->first_discount > 0)
        {
            $table->addRow(array(
                ___('Coupon Discount'),
                $invoice->getCurrency($invoice->first_discount)
            ));
        }

        if ($invoice->first_tax > 0)
        {
            $table->addRow(array(
                ___('Tax Amount'),
                $invoice->getCurrency($invoice->first_tax)
            ));
        }

        $table->addRow(array(
            ___('Total'),
            $invoice->getCurrency($invoice->first_total)
        ))->addStyle($styleBold);


        $pointer = $page->drawTable($table, 0, $pointer);
        $page->nl($pointer);

        $termsText = new Am_TermsText($invoice);
        $page->drawTextWithFixedWidth(___('Subscription Terms') . ': ' . $termsText, $left, $pointer, $this->getPaperWidth() - 2 * $padd);
        $page->nl($pointer);

        if (!$this->getDi()->config->get('invoice_custom_template') ||
            !$this->getDi()->uploadTable->load($this->getDi()->config->get('invoice_custom_template')))
        {
            if ($ifn = $this->getDi()->config->get('invoice_footer_note'))
            {
                $page->nl($pointer);
                $page->drawTextWithFixedWidth($ifn, $left, $pointer, $this->getPaperWidth() - 2 * $padd);
            }
        }
        return $pdf->render();
    }

    protected function getState(Invoice $invoice)
    {
        $state = $this->getDi()->stateTable->findFirstBy(array(
                'state' => $invoice->getState()
            ));
        return $state ? $state->title : $invoice->getState();
    }

    protected function getCountry(Invoice $invoice)
    {
        $country = $this->getDi()->countryTable->findFirstBy(array(
                'country' => $invoice->getCountry()
            ));
        return $country->title;
    }

}

