<?php
/** Small, dependency-free E.164 normalizer for public registration. */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }
class internationalphonenumber extends ChisimbaObject
{
    public function callingCodes()
    {
        return array(
            '+27'=>'South Africa (+27)','+1'=>'Canada / United States (+1)','+7'=>'Kazakhstan / Russia (+7)',
            '+20'=>'Egypt (+20)','+211'=>'South Sudan (+211)','+212'=>'Morocco (+212)','+213'=>'Algeria (+213)',
            '+216'=>'Tunisia (+216)','+218'=>'Libya (+218)','+220'=>'Gambia (+220)','+221'=>'Senegal (+221)',
            '+222'=>'Mauritania (+222)','+223'=>'Mali (+223)','+224'=>'Guinea (+224)','+225'=>"Côte d’Ivoire (+225)",
            '+226'=>'Burkina Faso (+226)','+227'=>'Niger (+227)','+228'=>'Togo (+228)','+229'=>'Benin (+229)',
            '+230'=>'Mauritius (+230)','+231'=>'Liberia (+231)','+232'=>'Sierra Leone (+232)','+233'=>'Ghana (+233)',
            '+234'=>'Nigeria (+234)','+235'=>'Chad (+235)','+236'=>'Central African Republic (+236)',
            '+237'=>'Cameroon (+237)','+238'=>'Cabo Verde (+238)','+239'=>'São Tomé and Príncipe (+239)',
            '+240'=>'Equatorial Guinea (+240)','+241'=>'Gabon (+241)','+242'=>'Congo (+242)',
            '+243'=>'DR Congo (+243)','+244'=>'Angola (+244)','+245'=>'Guinea-Bissau (+245)',
            '+248'=>'Seychelles (+248)','+249'=>'Sudan (+249)','+250'=>'Rwanda (+250)','+251'=>'Ethiopia (+251)',
            '+252'=>'Somalia (+252)','+253'=>'Djibouti (+253)','+254'=>'Kenya (+254)','+255'=>'Tanzania (+255)',
            '+256'=>'Uganda (+256)','+257'=>'Burundi (+257)','+258'=>'Mozambique (+258)','+260'=>'Zambia (+260)',
            '+261'=>'Madagascar (+261)','+263'=>'Zimbabwe (+263)','+264'=>'Namibia (+264)','+265'=>'Malawi (+265)',
            '+266'=>'Lesotho (+266)','+267'=>'Botswana (+267)','+268'=>'Eswatini (+268)','+269'=>'Comoros (+269)',
            '+246'=>'Diego Garcia (+246)','+247'=>'Ascension Island (+247)','+262'=>'Réunion / Mayotte (+262)',
            '+290'=>'Saint Helena / Tristan da Cunha (+290)','+291'=>'Eritrea (+291)','+297'=>'Aruba (+297)',
            '+298'=>'Faroe Islands (+298)','+299'=>'Greenland (+299)',
            '+30'=>'Greece (+30)','+31'=>'Netherlands (+31)','+32'=>'Belgium (+32)','+33'=>'France (+33)',
            '+34'=>'Spain (+34)','+36'=>'Hungary (+36)','+39'=>'Italy (+39)','+40'=>'Romania (+40)',
            '+41'=>'Switzerland (+41)','+43'=>'Austria (+43)','+44'=>'United Kingdom (+44)','+45'=>'Denmark (+45)',
            '+46'=>'Sweden (+46)','+47'=>'Norway (+47)','+48'=>'Poland (+48)','+49'=>'Germany (+49)',
            '+351'=>'Portugal (+351)','+352'=>'Luxembourg (+352)','+353'=>'Ireland (+353)','+354'=>'Iceland (+354)',
            '+355'=>'Albania (+355)','+356'=>'Malta (+356)','+357'=>'Cyprus (+357)','+358'=>'Finland (+358)',
            '+359'=>'Bulgaria (+359)','+370'=>'Lithuania (+370)','+371'=>'Latvia (+371)','+372'=>'Estonia (+372)',
            '+373'=>'Moldova (+373)','+374'=>'Armenia (+374)','+375'=>'Belarus (+375)','+376'=>'Andorra (+376)',
            '+377'=>'Monaco (+377)','+380'=>'Ukraine (+380)','+381'=>'Serbia (+381)','+382'=>'Montenegro (+382)',
            '+385'=>'Croatia (+385)','+386'=>'Slovenia (+386)','+387'=>'Bosnia and Herzegovina (+387)',
            '+389'=>'North Macedonia (+389)','+420'=>'Czechia (+420)','+421'=>'Slovakia (+421)',
            '+350'=>'Gibraltar (+350)','+378'=>'San Marino (+378)','+383'=>'Kosovo (+383)','+423'=>'Liechtenstein (+423)',
            '+51'=>'Peru (+51)','+52'=>'Mexico (+52)','+53'=>'Cuba (+53)','+54'=>'Argentina (+54)',
            '+55'=>'Brazil (+55)','+56'=>'Chile (+56)','+57'=>'Colombia (+57)','+58'=>'Venezuela (+58)',
            '+501'=>'Belize (+501)','+502'=>'Guatemala (+502)','+503'=>'El Salvador (+503)','+504'=>'Honduras (+504)',
            '+505'=>'Nicaragua (+505)','+506'=>'Costa Rica (+506)','+507'=>'Panama (+507)',
            '+500'=>'Falkland Islands (+500)','+508'=>'Saint Pierre and Miquelon (+508)','+509'=>'Haiti (+509)',
            '+590'=>'Guadeloupe / Saint Martin (+590)','+591'=>'Bolivia (+591)','+592'=>'Guyana (+592)',
            '+593'=>'Ecuador (+593)','+594'=>'French Guiana (+594)','+595'=>'Paraguay (+595)',
            '+596'=>'Martinique (+596)','+597'=>'Suriname (+597)','+598'=>'Uruguay (+598)','+599'=>'Caribbean Netherlands (+599)',
            '+60'=>'Malaysia (+60)','+61'=>'Australia (+61)','+62'=>'Indonesia (+62)','+63'=>'Philippines (+63)',
            '+64'=>'New Zealand (+64)','+65'=>'Singapore (+65)','+66'=>'Thailand (+66)',
            '+670'=>'Timor-Leste (+670)','+672'=>'Australian territories (+672)','+673'=>'Brunei (+673)',
            '+674'=>'Nauru (+674)','+675'=>'Papua New Guinea (+675)','+676'=>'Tonga (+676)',
            '+677'=>'Solomon Islands (+677)','+678'=>'Vanuatu (+678)','+679'=>'Fiji (+679)',
            '+680'=>'Palau (+680)','+681'=>'Wallis and Futuna (+681)','+682'=>'Cook Islands (+682)',
            '+683'=>'Niue (+683)','+685'=>'Samoa (+685)','+686'=>'Kiribati (+686)','+687'=>'New Caledonia (+687)',
            '+688'=>'Tuvalu (+688)','+689'=>'French Polynesia (+689)','+690'=>'Tokelau (+690)',
            '+691'=>'Micronesia (+691)','+692'=>'Marshall Islands (+692)',
            '+81'=>'Japan (+81)','+82'=>'South Korea (+82)','+84'=>'Vietnam (+84)','+86'=>'China (+86)',
            '+850'=>'North Korea (+850)','+852'=>'Hong Kong (+852)','+853'=>'Macao (+853)',
            '+855'=>'Cambodia (+855)','+856'=>'Laos (+856)','+886'=>'Taiwan (+886)',
            '+90'=>'Türkiye (+90)','+91'=>'India (+91)','+92'=>'Pakistan (+92)','+93'=>'Afghanistan (+93)',
            '+94'=>'Sri Lanka (+94)','+95'=>'Myanmar (+95)','+98'=>'Iran (+98)',
            '+880'=>'Bangladesh (+880)','+960'=>'Maldives (+960)','+961'=>'Lebanon (+961)','+962'=>'Jordan (+962)',
            '+963'=>'Syria (+963)','+964'=>'Iraq (+964)','+965'=>'Kuwait (+965)','+966'=>'Saudi Arabia (+966)',
            '+967'=>'Yemen (+967)','+968'=>'Oman (+968)','+971'=>'United Arab Emirates (+971)',
            '+972'=>'Israel (+972)','+974'=>'Qatar (+974)','+975'=>'Bhutan (+975)','+976'=>'Mongolia (+976)',
            '+977'=>'Nepal (+977)','+992'=>'Tajikistan (+992)','+993'=>'Turkmenistan (+993)',
            '+994'=>'Azerbaijan (+994)','+995'=>'Georgia (+995)','+996'=>'Kyrgyzstan (+996)',
            '+998'=>'Uzbekistan (+998)'
        );
    }
    public function defaultCallingCode($configured)
    {
        $code=$this->callingCode($configured);
        return $code!==null&&isset($this->callingCodes()[$code])?$code:'+27';
    }
    public function normalize($callingCode,$number)
    {
        $number=is_scalar($number)?trim((string)$number):'';
        if($number===''||preg_match('/[^0-9+().\-\s]/',$number)) return null;
        $compact=preg_replace('/[().\-\s]/','',$number);
        if(str_starts_with($compact,'00')) $compact='+'.substr($compact,2);
        if(str_starts_with($compact,'+')) return $this->e164($compact);
        $code=$this->callingCode($callingCode); if($code===null) return null;
        if(str_starts_with($compact,'0')) $compact=substr($compact,1);
        return $this->e164($code.$compact);
    }
    private function callingCode($value)
    {
        $value=is_scalar($value)?trim((string)$value):'';
        if(preg_match('/^\+?[1-9][0-9]{0,2}$/',$value)!==1) return null;
        $code='+'.ltrim($value,'+');
        return isset($this->callingCodes()[$code])?$code:null;
    }
    private function e164($value)
    {
        if(preg_match('/^\+[1-9][0-9]{7,14}$/',$value)!==1) return null;
        foreach(array_keys($this->callingCodes()) as $code) if(str_starts_with($value,$code)) return $value;
        return null;
    }
}
?>
