<?php
/** Fixed, safe portrait-A4 certificate renderer. No arbitrary HTML is accepted. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class certificatepdfrenderer extends ChisimbaObject
{
    private const WIDTH=1654; private const HEIGHT=2339;
    public function render(array $issuance)
    {
        if(!extension_loaded('gd')){throw new RuntimeException('Certificate rendering requires the PHP GD extension');}
        $snapshot=isset($issuance['snapshot'])?$issuance['snapshot']:json_decode($issuance['snapshot_json'],true);
        if(!is_array($snapshot)||empty($snapshot['base'])||empty($snapshot['signer'])){throw new InvalidArgumentException('Invalid certificate snapshot');}
        $base=$snapshot['base'];$signer=$snapshot['signer'];$im=imagecreatetruecolor(self::WIDTH,self::HEIGHT);imageantialias($im,true);
        $paper=imagecolorallocate($im,253,252,248);$primary=$this->colour($im,$base['primary_colour']);$accent=$this->colour($im,$base['accent_colour']);$white=imagecolorallocate($im,255,255,255);
        imagefilledrectangle($im,0,0,self::WIDTH,self::HEIGHT,$paper);imagesetthickness($im,6);imagerectangle($im,68,68,1585,2270,$accent);imagesetthickness($im,2);imagerectangle($im,88,88,1565,2250,$accent);imagefilledellipse($im,827,255,150,150,$accent);
        $sans=$this->font('DejaVuSans.ttf');$sansBold=$this->font('DejaVuSans-Bold.ttf');$serif=$this->font('DejaVuSerif.ttf');$serifBold=$this->font('DejaVuSerif-Bold.ttf');$serifItalic=$serifBold;
        $this->centred($im,'CERTIFICATE',260,20,$sansBold,$white);$this->centred($im,'CERTIFICATE OF',455,49,$serifBold,$primary);$this->centred($im,'COMPLETION',550,59,$serifBold,$primary);$this->centred($im,'AWARDED TO',755,22,$sansBold,$accent);
        $this->centredFit($im,$snapshot['recipient_name'],910,64,1320,$serifItalic,$primary,34);$this->centred($im,'for the completion of the course',1078,25,$serif,$primary);$this->centredFit($im,$snapshot['resource_title'],1200,50,1320,$serifBold,$primary,28);
        $this->centred($im,'Completed on '.date('F j, Y',strtotime($snapshot['completed_at'])),1395,24,$serif,$primary);
        imagesetthickness($im,2);imageline($im,210,1745,690,1745,$primary);imageline($im,964,1745,1444,1745,$primary);
        $this->placeAsset($im,$signer['signature_path']??'',280,1570,340,150);$this->placeAsset($im,$base['logo_path']??'',1060,1550,290,170);
        $this->centredIn($im,$signer['name'],210,690,1810,25,$serifBold,$primary);$this->centredIn($im,$signer['title']?:'Signer',210,690,1860,18,$serif,$primary);$this->centredIn($im,$base['organisation'],964,1444,1810,25,$serifBold,$primary);$this->centredIn($im,'Organisation',964,1444,1860,18,$serif,$primary);
        $this->centred($im,$base['website_url']?:'',2070,18,$sans,$primary);$this->centred($im,$base['company_name'],2110,18,$sansBold,$primary);$this->centred($im,$base['company_location']?:'',2150,18,$sans,$primary);$this->centred($im,'Certificate number: '.$issuance['certificate_number'],2230,14,$sans,$primary);
        ob_start();imagejpeg($im,null,95);$jpeg=ob_get_clean();imagedestroy($im);return (new certificate_service_pdf_document())->wrapJpeg($jpeg,self::WIDTH,self::HEIGHT);
    }
    private function centredFit($im,$text,$y,$size,$max,$font,$colour,$minimum){while($size>$minimum&&$this->textWidth($text,$size,$font)>$max){$size--;} $this->centred($im,$text,$y,$size,$font,$colour);}
    private function centred($im,$text,$y,$size,$font,$colour){$text=$this->safe($text);$width=$this->textWidth($text,$size,$font);imagettftext($im,$size,0,(int)((self::WIDTH-$width)/2),$y,$colour,$font,$text);}
    private function centredIn($im,$text,$left,$right,$y,$size,$font,$colour){$text=$this->safe($text);$width=$this->textWidth($text,$size,$font);imagettftext($im,$size,0,(int)($left+(($right-$left-$width)/2)),$y,$colour,$font,$text);}
    private function textWidth($text,$size,$font){$box=imagettfbbox($size,0,$font,$this->safe($text));return abs($box[2]-$box[0]);}
    private function safe($text){return trim(preg_replace('/\s+/u',' ',(string)$text));}
    private function colour($im,$hex){$hex=ltrim((string)$hex,'#');return imagecolorallocate($im,hexdec(substr($hex,0,2)),hexdec(substr($hex,2,2)),hexdec(substr($hex,4,2)));}
    private function placeAsset($canvas,$path,$x,$y,$maxWidth,$maxHeight){if($path===''||!is_file($path)||!is_readable($path)){return;}$data=@file_get_contents($path);$source=$data===false?false:@imagecreatefromstring($data);if(!$source){return;}$width=imagesx($source);$height=imagesy($source);$scale=min($maxWidth/$width,$maxHeight/$height,1);$targetWidth=(int)($width*$scale);$targetHeight=(int)($height*$scale);imagecopyresampled($canvas,$source,$x+(int)(($maxWidth-$targetWidth)/2),$y+(int)(($maxHeight-$targetHeight)/2),0,0,$targetWidth,$targetHeight,$width,$height);imagedestroy($source);}
    private function font($name){$path=dirname(__DIR__).'/resources/fonts/'.$name;if(is_file($path)&&is_readable($path)){return $path;}throw new RuntimeException('Bundled certificate font is missing: '.$name);}
}
/** Minimal self-contained PDF wrapper for the rendered A4 certificate page. */
class certificate_service_pdf_document
{
    public function wrapJpeg($jpeg,$pixelWidth,$pixelHeight)
    {
        $content="q\n595.28 0 0 841.89 0 0 cm\n/CertificatePage Do\nQ\n";$objects=array('<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /XObject << /CertificatePage 5 0 R >> >> /Contents 4 0 R >>','<< /Length '.strlen($content).">>\nstream\n".$content.'endstream','<< /Type /XObject /Subtype /Image /Width '.(int)$pixelWidth.' /Height '.(int)$pixelHeight.' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($jpeg).">>\nstream\n".$jpeg."\nendstream");
        $pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";$offsets=array();foreach($objects as $i=>$object){$offsets[]=strlen($pdf);$pdf.=($i+1)." 0 obj\n".$object."\nendobj\n";}$xref=strlen($pdf);$pdf.="xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";foreach($offsets as $offset){$pdf.=sprintf("%010d 00000 n \n",$offset);}return $pdf.'trailer << /Size '.(count($objects)+1).' /Root 1 0 R >>'."\nstartxref\n".$xref."\n%%EOF\n";
    }
}
?>
