<?php


class Am_Pdf_Table {
    protected $rows = array();
    protected $data;
    protected $width = null;
    protected $stylesColl = array();
    protected $stylesRow = array();
    const TOP = 1;
    const RIGHT = 2;
    const BOTTOM = 3;
    const LEFT = 4;
    protected $margin = array(
            self::TOP => 0,
            self::RIGHT => 0,
            self::BOTTOM => 0,
            self::LEFT => 0
    );

    public function setData($data) {
        $this->data = $data;
        foreach($data as $rowData) {
            $row = new Am_Pdf_Row();
            $row->setData($rowData);
            $this->rows[] = $row;
        }
    }

    public function setStyleForColumn($colNum, $style) {
        $this->stylesColl[$colNum] = $style;
    }
    public function setStyleForRow($rowNum, $style) {
        $this->stylesRow[$rowNum] = $style;
    }

    public function getStyleForRow($rowNum) {
        if (isset($this->stylesRow[$rowNum])) {
            return $this->stylesRow[$rowNum];
        } else {
            return array();
        }
    }

    public function getStyleForColumn($colNum) {
        if (isset($this->stylesColl[$colNum])) {
            return $this->stylesColl[$colNum];
        } else {
            return array();
        }
    }

    public function setMargin($top=0, $right=0, $bottom=0, $left=0) {
        $this->margin = array(
                self::TOP => $top,
                self::RIGHT => $right,
                self::BOTTOM => $bottom,
                self::LEFT => $left
        );
    }

    public function getMargin($side) {
        return $this->margin[$side];
    }

    /**
     *
     * @param array $rowData
     * @return Am_Pdf_Row
     * 
     */
    public function addRow($rowData) {
        $row = new Am_Pdf_Row();
        $row->setData($rowData);
        $this->rows[] = $row;
        return $row;
    }

    public function setWidth($width) {
        $this->width = $width;
    }

    protected function getRows() {
        return $this->rows;
    }

    public function render(Am_Pdf_Page_Decorator $page, $x, $y) {

        $this->width =  $this->width ? $this->width : $page->getWidth() - $x;

        $rowNum = 1;
        foreach ($this->getRows() as $row) {
            $row->setTable($this);
            $row->setWidth(
                    $this->width - $this->getMargin(self::LEFT) -
                    $this->getMargin(self::RIGHT)
            );

            $row->addStyle($this->getStyleForRow($rowNum));
            $row->render($page, $x + $this->getMargin(self::LEFT),
                    $y - $this->getMargin(self::TOP));
            $y = $y - $row->getHeight($page);
            $rowNum++;
        }

        return $y;
    }
}
