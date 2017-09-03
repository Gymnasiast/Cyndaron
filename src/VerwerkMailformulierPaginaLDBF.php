<?php
namespace Cyndaron;


class VerwerkMailformulierPaginaLDBF extends Pagina
{
    public function __construct()
    {
//        $id = intval(Request::geefGetVeilig('id'));
//        $this->connectie = DBConnection::getPDO();
//        $formprep = $this->connectie->prepare('SELECT * FROM mailformulieren WHERE id=?');
//        $formprep->execute([$id]);
//        $form = $formprep->fetch();
//        $tekst = '';

        if (!Request::postIsLeeg()) //$form['naam'])
        {
            if (/*$form['stuur_bevestiging'] == true && */empty(Request::geefPostVeilig('E-mailadres')))
            {
                parent::__construct('Formulier versturen mislukt');
                $this->maakNietDelen(true);
                $this->toonPrePagina();
                echo 'U heeft uw e-mailadres niet of niet goed ingevuld. Klik op Vorige om het te herstellen.';
                $this->toonPostPagina();
            }
            else/*if (strtolower(Request::geefPostVeilig('antispam')) == strtolower($form['antispamantwoord']))*/
            {
                //                foreach (Request::geefPostArrayVeilig() as $vraag => $antwoord)
                //                {
                //                    if ($vraag !== 'antispam')
                //                    {
                //                        $tekst .= $vraag . ': ' . strtr($antwoord, ['\\' => '']) . "\n";
                //                    }
                //                }

                $tekst = '<!DOCTYPE html><html><head><title>Aanvraag</title><body><table>
	<tbody>
		<tr>
			<td>De heer/mevrouw:</td>
			<td>' . Request::geefPostVeilig("geslacht") . '</td>
		</tr>
		<tr>
			<td>Voorletters:</td>
			<td>' . Request::geefPostVeilig("voorletters") . '</td>
		</tr>
		<tr>
			<td>Achternaam:</td>
			<td>' . Request::geefPostVeilig("achternaam") . '</td>
		</tr>
		<tr>
			<td>Adres:</td>
			<td>' . Request::geefPostVeilig("adres") . '</td>
		</tr>
		<tr>
			<td>Postcode</td>
			<td>' . Request::geefPostVeilig("postcode") . '</td>
		</tr>
		<tr>
			<td>Woonplaats</td>
			<td>' . Request::geefPostVeilig("woonplaats") . '</td>
		</tr>
		<tr>
			<td>Telefoon/GSM:</td>
			<td>' . Request::geefPostVeilig("telefoon") . '</td>
		</tr>
		<tr>
			<td>E-mailadres:</td>
			<td>' . Request::geefPostVeilig("E-mailadres") . '</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2">Vraagt hierbij een financi&euml;le ondersteuning (*) aan bij het Leen de Broekert Fonds voor:</td>
		</tr>
		<tr>
			<td>Voornamen:</td>
			<td>' . Request::geefPostVeilig("bvoornamen") . '</td>
		</tr>
		<tr>
			<td>Achternaam:</td>
			<td>' . Request::geefPostVeilig("bachternaam") . '</td>
		</tr>
		<tr>
			<td>Woonadres:</td>
			<td>' . Request::geefPostVeilig("bwoonadres") . '</td>
		</tr>
		<tr>
			<td>Geboortedatum:</td>
			<td>' . Request::geefPostVeilig("geboortedag") . '-' . Request::geefPostVeilig("geboortemaand") . '-' . Request::geefPostVeilig("geboortejaar") . '</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="2">De financi&euml;le ondersteuning betreft een bijdrage ten behoeve van:</td>
		</tr>
		<tr>
			<td>Instrumentale/Vocale studie:</td>
			<td>' . Request::geefPostVeilig("studie") . '</td>
		</tr>
		<tr>
			<td>Betrokkene volgde hiervoor:</td>
			<td>' . Request::geefPostVeilig("lessen") . ' lessen</td>
		</tr>
		<tr>
			<td>Eerder werd les gevolgd bij:</td>
			<td>' . Request::geefPostVeilig("lesdocent") . '</td>
		</tr>
		<tr>
			<td>Aantal gevolgde lesjaren:</td>
			<td>' . Request::geefPostVeilig("aantal") . '</td>
		</tr>
		<tr>
			<td>Vervolgstudie bij:</td>
			<td>' . Request::geefPostVeilig("vervolg") . '</td>
		</tr>
		<tr>
			<td colspan="2">' . Request::geefPostVeilig("soort") . '</td>
		</tr>
		<tr>
			<td>Huurgebruik / Aanschaf instrument:</td>
			<td>' . Request::geefPostVeilig("huur") . '</td>
		</tr>
		<tr>
			<td>Anderszins:</td>
			<td>' . Request::geefPostVeilig("anderszins") . '</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>Aanvrager verklaart hierbij dat het bruto gezinsinkomen:</td>
			<td>&euro; ' . Request::geefPostVeilig("gezinsinkomen") . ' bedraagt.</td>
		</tr>
		<tr>
			<td colspan="2"><b>Aanvraag voor financi&euml;le ondersteuning wordt niet toegekend, indien het gezinsinkomen meer dan 120 % van de toepasselijke bijstandsnorm bedraagt.</b></td>
		</tr>
		<tr>
			<td>Aanvrager meldt hierbij dat er ook een aanvraag is ingediend bij:<br />
			(indien van toepassing)</td>
			<td>' . Request::geefPostVeilig("ookaanvraag") . '</td>
		</tr>
	</tbody>
</table></body></html>';

                $extraheaders = 'From: "Website Leen de Broekert Fonds" <noreply@leendebroekertfonds.nl>' . "\n" .
                                'Content-Type: text/html; charset="UTF-8"';

                $mail1 = mail('voorzitter@leendebroekertfonds.nl', 'Nieuwe aanvraag', $tekst, $extraheaders);
                $mail2 = mail(Request::geefPostVeilig('E-mailadres'), 'Kopie aanvraag', $tekst, $extraheaders);

                if ($mail1 && $mail2)
                {
                    parent::__construct('Formulier verstuurd');
                    $this->maakNietDelen(true);
                    $this->toonPrePagina();
                    echo 'Het versturen is gelukt.';

//                    if ($form['stuur_bevestiging'] == true && Request::geefPostVeilig('E-mailadres'))
//                    {
//                        $extraheaders = sprintf('From: %s <noreply@%s>', html_entity_decode(Instelling::geefInstelling('websitenaam')), $server);
//                        mail($afzender, 'Ontvangstbevestiging', $form['tekst_bevestiging'], $extraheaders);
//                    }
                }
                else
                {
                    parent::__construct('Formulier versturen mislukt');
                    $this->maakNietDelen(true);
                    $this->toonPrePagina();
                    echo 'Wegens een technisch probleem is het versturen niet gelukt';
                }
                $this->toonPostPagina();
            }
//            else
//            {
//                parent::__construct('Formulier versturen mislukt');
//                $this->maakNietDelen(true);
//                $this->toonPrePagina();
//                echo 'U heeft de antispamvraag niet of niet goed ingevuld. Klik op Vorige om het te herstellen.';
//                $this->toonPostPagina();
//            }
        }
        else
        {
            parent::__construct('Formulier versturen mislukt');
            $this->maakNietDelen(true);
            $this->toonPrePagina();
            echo 'Ongeldig formulier.';
            $this->toonPostPagina();
        }
    }
}