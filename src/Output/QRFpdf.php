<?php
/**
 * Class QRFpdf
 *
 * https://github.com/chillerlan/php-qrcode/pull/49
 *
 * @filesource   QRFpdf.php
 * @created      03.06.2020
 * @package      chillerlan\QRCode\Output
 * @author       Maximilian Kresse
 *
 * @license      MIT
 */

declare(strict_types=1);

namespace chillerlan\QRCode\Output;

use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\Settings\SettingsContainerInterface;

/**
 * QRFpdf output module (requires fpdf)
 *
 * @see https://github.com/Setasign/FPDF
 * @see http://www.fpdf.org/
 */
class QRFpdf extends QROutputAbstract
{
    public function __construct(SettingsContainerInterface $options, QRMatrix $matrix)
    {
        parent::__construct($options, $matrix);

        if (!\class_exists(\FPDF::class)) {
            throw new \BadMethodCallException(
                'The QRFpdf output requires FPDF as dependency but the class "\FPDF" couldn\'t be found.'
            );
        }
    }

    /**
     * @inheritDoc
     */
    protected function setModuleValues(): void
    {
        foreach ($this::DEFAULT_MODULE_VALUES as $M_TYPE => $defaultValue) {
            $v = $this->options->moduleValues[$M_TYPE] ?? null;

            if (!\is_array($v) || \count($v) < 3) {
                $this->moduleValues[$M_TYPE] = $defaultValue
                    ? [0, 0, 0]
                    : [255, 255, 255];
            } else {
                $this->moduleValues[$M_TYPE] = \array_values($v);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function dump(string $file = null): string
    {
        $file ??= $this->options->cachefile;

        $fpdf = new \FPDF('P', 'pt', [$this->length, $this->length]);
        $fpdf->AddPage();

        $prevColor = null;
        foreach ($this->matrix->matrix() as $y => $row) {
            foreach ($row as $x => $M_TYPE) {
                /**
                 * @var int $M_TYPE
                 */
                $color = $this->moduleValues[$M_TYPE];
                if ($prevColor === null || $prevColor !== $color) {
                    $fpdf->SetFillColor(...$color);
                    $prevColor = $color;
                }
                $fpdf->Rect($x * $this->scale, $y * $this->scale, 1 * $this->scale, 1 * $this->scale, 'F');
            }
        }

        $pdfData = $fpdf->Output('S');

        if ($file !== null) {
            $this->saveToFile($pdfData, $file);
        }

        return $pdfData;
    }
}
