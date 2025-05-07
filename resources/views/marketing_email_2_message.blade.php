<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8"> <!-- utf-8 works for most cases -->
    <meta name="viewport" content="width=device-width"> <!-- Forcing initial-scale shouldn't be necessary -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"> <!-- Use the latest (edge) version of IE rendering engine -->
    <meta name="x-apple-disable-message-reformatting">  <!-- Disable auto-scale in iOS 10 Mail entirely -->
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no"> <!-- Tell iOS not to automatically link certain text strings. -->
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title></title> <!-- The title tag shows in email notifications, like Android 4.4. -->

    <!-- What it does: Makes background images in 72ppi Outlook render at correct size. -->
    <!--[if gte mso 9]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->

    <!-- Web Font / @font-face : BEGIN -->
    <!-- NOTE: If web fonts are not required, lines 23 - 41 can be safely removed. -->

    <!-- Desktop Outlook chokes on web font references and defaults to Times New Roman, so we force a safe fallback font. -->
    <!--[if mso]>
        <style>
            * {
                font-family: sans-serif !important;
            }
        </style>
    <![endif]-->

    <!-- All other clients get the webfont reference; some will render the font and others will silently fail to the fallbacks. More on that here: https://web.archive.org/web/20190717120616/http://stylecampaign.com/blog/2015/02/webfont-support-in-email/ -->
    <!--[if !mso]><!-->
    <!-- insert web font reference, eg: <link href='https://fonts.googleapis.com/css?family=Roboto:400,700' rel='stylesheet' type='text/css'> -->
    <!--<![endif]-->

    <!-- Web Font / @font-face : END -->

    <!-- CSS Reset : BEGIN -->
    <style>

        /* What it does: Tells the email client that both light and dark styles are provided. A duplicate of meta color-scheme meta tag above. */
        :root {
          color-scheme: light dark;
          supported-color-schemes: light dark;
        }

        /* What it does: Remove spaces around the email design added by some email clients. */
        /* Beware: It can remove the padding / margin and add a background color to the compose a reply window. */
        html,
        body {
            margin: 0 auto !important;
            padding: 0 !important;
            height: 100% !important;
            width: 100% !important;
        }

        /* What it does: Stops email clients resizing small text. */
        * {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }

        /* What it does: Centers email on Android 4.4 */
        div[style*="margin: 16px 0"] {
            margin: 0 !important;
        }

        /* What it does: forces Samsung Android mail clients to use the entire viewport */
        #MessageViewBody, #MessageWebViewDiv{
            width: 100% !important;
        }

        /* What it does: Stops Outlook from adding extra spacing to tables. */
        table,
        td {
            mso-table-lspace: 0pt !important;
            mso-table-rspace: 0pt !important;
        }

        /* What it does: Fixes webkit padding issue. */
        table {
            border-spacing: 0 !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 0 auto !important;
        }

        /* What it does: Uses a better rendering method when resizing images in IE. */
        img {
            -ms-interpolation-mode:bicubic;
        }

        /* What it does: Prevents Windows 10 Mail from underlining links despite inline CSS. Styles for underlined links should be inline. */
        a {
            text-decoration: none;
        }

        /* What it does: A work-around for email clients meddling in triggered links. */
        a[x-apple-data-detectors],  /* iOS */
        .unstyle-auto-detected-links a,
        .aBn {
            border-bottom: 0 !important;
            cursor: default !important;
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }

        /* What it does: Prevents Gmail from displaying a download button on large, non-linked images. */
        .a6S {
            display: none !important;
            opacity: 0.01 !important;
        }

        /* What it does: Prevents Gmail from changing the text color in conversation threads. */
        .im {
            color: inherit !important;
        }

        /* If the above doesn't work, add a .g-img class to any image in question. */
        img.g-img + div {
            display: none !important;
        }

        /* What it does: Removes right gutter in Gmail iOS app: https://github.com/TedGoas/Cerberus/issues/89  */
        /* Create one of these media queries for each additional viewport size you'd like to fix */

        /* iPhone 4, 4S, 5, 5S, 5C, and 5SE */
        @media only screen and (min-device-width: 320px) and (max-device-width: 374px) {
            u ~ div .email-container {
                min-width: 320px !important;
            }
        }
        /* iPhone 6, 6S, 7, 8, and X */
        @media only screen and (min-device-width: 375px) and (max-device-width: 413px) {
            u ~ div .email-container {
                min-width: 375px !important;
            }
        }
        /* iPhone 6+, 7+, and 8+ */
        @media only screen and (min-device-width: 414px) {
            u ~ div .email-container {
                min-width: 414px !important;
            }
        }

    </style>
    <!-- CSS Reset : END -->

    <!-- Progressive Enhancements : BEGIN -->
    <style>

	    /* What it does: Hover styles for buttons */
	    .button-td,
	    .button-a {
	        transition: all 100ms ease-in;
	    }
	    .button-td-primary:hover,
	    .button-a-primary:hover {
	        background: #2b7d08 !important;
	        border-color: #2b7d08 !important;
	    }

	    /* Media Queries */
	    @media screen and (max-width: 600px) {

	        /* What it does: Adjust typography on small screens to improve readability */
	        .email-container p {
	            font-size: 17px !important;
	        }

	    }

        /* Dark Mode Styles : BEGIN */
        @media (prefers-color-scheme: dark) {
			.email-bg {
				background: #111111 !important;
			}
            .darkmode-bg {
                background: #7C82C5 !important;
            }
			h1,
			h2,
			h3,
			p,
			li,
			.darkmode-text,
			.email-container a:not([class]) {
				color: #F7F7F9 !important;
			}
			td.button-td-primary,
			td.button-td-primary a {
				background: #ffffff !important;
				border-color: #ffffff !important;
				color: #7C82C5 !important;
			}
			td.button-td-primary:hover,
			td.button-td-primary a:hover {
				background: #2b7d08 !important;
				border-color: #2b7d08 !important;
			}
			.footer td {
				color: #aaaaaa !important;
			}
            .darkmode-fullbleed-bg {
                background-color: #0F3016 !important;
            }
		}
        /* Dark Mode Styles : END */
    </style>
    <!-- Progressive Enhancements : END -->

</head>
<!--
	The email background color (#7C82C5) is defined in three places:
	1. body tag: for most email clients
	2. center tag: for Gmail and Inbox mobile apps and web versions of Gmail, GSuite, Inbox, Yahoo, AOL, Libero, Comcast, freenet, Mail.ru, Orange.fr
	3. mso conditional: For Windows 10 Mail
-->
<body width="100%" style="margin: 0; padding: 0 !important; mso-line-height-rule: exactly; background-color: #7C82C5;" class="email-bg">
	<center role="article" aria-roledescription="email" lang="en" style="width: 100%; background-color: #7C82C5;" class="email-bg">
    <!--[if mso | IE]>
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #7C82C5;" class="email-bg">
    <tr>
    <td>
    <![endif]-->

        <!-- Visually Hidden Preheader Text : BEGIN -->
        <div style="max-height:0; overflow:hidden; mso-hide:all;" aria-hidden="true">
            ¿Todavía no eres miembro?
        </div>
        <!-- Visually Hidden Preheader Text : END -->

        <!-- Create white space after the desired preview text so email clients don’t pull other distracting text into the inbox preview. Extend as necessary. -->
        <!-- Preview Text Spacing Hack : BEGIN -->
        <div style="display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all; font-family: sans-serif;">
	        &zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
        </div>
        <!-- Preview Text Spacing Hack : END -->

        <!--
            Set the email width. Defined in two places:
            1. max-width for all clients except Desktop Windows Outlook, allowing the email to squish on narrow but never go wider than 600px.
            2. MSO tags for Desktop Windows Outlook enforce a 600px width.
        -->
        <div style="max-width: 600px; margin: 0 auto;" class="email-container">
            <!--[if mso]>
            <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" width="600">
            <tr>
            <td>
            <![endif]-->

	        <!-- Email Body : BEGIN -->
	        <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: auto;">
		        <!-- Email Header : BEGIN -->
	            <tr>
	                <td style="padding: 20px 0; text-align: center">
	                  <!--  <img src="https://api.weloveracket.com/assets/img/emails/email-logo.jpg" width="200" height="50" alt="alt_text" border="0" style="height: auto; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555;">  -->
	                </td>
	            </tr>
		        <!-- Email Header : END -->

                <!-- Hero Image, Flush : BEGIN -->
                <tr>
                    <td style="background-color: #ffffff;" class="darkmode-bg">
                         <img src="https://api.weloveracket.com/assets/img/emails/email-logo.jpg" width="600" height="" alt="alt_text" border="0" style="width: 100%; max-width: 600px; height: 250px; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555; margin: auto; display: block;" class="g-img"> 
                    </td>
                </tr>
                <!-- Hero Image, Flush : END -->

                <!-- 1 Column Text + Button : BEGIN -->
                <tr>
                    <td style="background-color: #ffffff;" class="darkmode-bg">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            <tr>
                                <td style="padding: 20px; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555;">
                                    <h1 style="margin: 0 0 10px 0; font-family: sans-serif; font-size: 25px; line-height: 30px; color: #333333; font-weight: normal;">¿Qué es y para qué sirve WeLoveRacket?</h1>
                                    <p style="margin: 0;">
                                        Somos una aplicación que ayuda a gestionar tu club de pádel, tenis, pickleball de manera cómoda y fácil. Si te unes a WeLoveRacket tendrás acceso una parte privada 
                                        para la administración. Tus datos como son reservas de las pistas, torneos y ligas existentes de tu club, horarios, reservas y demás será visible para todos los usuarios en la parte pública de la web. 
                                        Utilízala un mes gratis y sin compromisos y sin tener que introducir datos de compra. 
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 0 20px;">
                                    <!-- Button : BEGIN -->
                                    <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: auto;">
                                        <tr>
                                            <td class="button-td button-td-primary" style="border-radius: 4px; background: #7C82C5;">
                                                <a class="button-a button-a-primary" href="https://weloveracket.com/register-club" style="background: #7C82C5; border: 1px solid #000000; font-family: sans-serif; font-size: 15px; line-height: 15px; text-decoration: none; padding: 13px 17px; color: #ffffff; display: block; border-radius: 4px;">QUIERO MI MES DE PRUEBA </a>
											</td>
                                        </tr>
                                    </table>
                                    <!-- Button : END -->
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 20px; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555;">
                                    <h2 style="margin: 0 0 10px 0; font-family: sans-serif; font-size: 18px; line-height: 22px; color: #333333; font-weight: bold;">Con WeLoveRacket podrás entre otras muchas operaciones:</h2>
                                    <ul style="padding: 0; margin: 0 0 10px 0; list-style-type: disc;">
										<li style="margin:0 0 10px 30px;" class="list-item-first">Gestionar reservas puntuales y concurrentes.</li>
										<li style="margin:0 0 10px 30px;">Administrar socios al igual que monitores.</li>
										<li style="margin: 0 0 0 30px;" class="list-item-last">Ilimitada creación y administración de ligas y torneos.</li>
									</ul>
                                    <p style="margin: 0;">
                                        WeLoveRacket ha empezado en Motril, siendo la aplicación por excedencia de la mayoría de los clubes pero estamos creciendo a pasos agigantados por lo que
                                        no dejes pasar la oportunidad de registrate y aprovechar la oferta de un mes sin ningún coste. Alguno de nuestros casos de éxito: 
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!-- 1 Column Text + Button : END -->

                <!-- 2 Even Columns : BEGIN -->
                <tr>
                    <td style="padding: 0 10px 40px 10px; background-color: #ffffff;" class="darkmode-bg">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td valign="top" width="50%">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td style="text-align: center; padding: 0 10px;">
                                                <img src="https://api.weloveracket.com/assets/img/emails/eli.jpg" width="120" height="" alt="alt_text" border="0" style="width: 70%; max-width: 120px; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555;">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: left; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555; padding: 10px 10px 0;">
                                                <p style="margin: 0;">Se acabaron los dolores de cabeza y pérdida de tiempo.</p>
                                                <p style="margin: 0; font-style: italic;">Eli Miranda, Club Padello, Motril</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td valign="top" width="50%">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td style="text-align: center; padding: 0 10px;">
                                                <img src="https://api.weloveracket.com/assets/img/emails/cristian.jpg" width="120" height="" alt="alt_text" border="0" style="width: 70%; max-width: 120px; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555;">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="text-align: left; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555; padding: 10px 10px 0;">
                                                <p style="margin: 0;">Desde el minuto uno me adapté a la aplicación y me ayuda a todas las gestiones de mi club.</p>
                                               <p style="margin: 0; font-style: italic;">Christian Castano, Club Nueva Marina, Motril</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!-- 2 Even Columns : END -->

                <!-- Clear Spacer : BEGIN -->
                <tr>
                    <td aria-hidden="true" height="40" style="font-size: 0px; line-height: 0px;">
                        &nbsp;
                    </td>
                </tr>
                <!-- Clear Spacer : END -->

                <!-- 1 Column Text : BEGIN -->
                <tr>
                    <td style="background-color: #ffffff;" class="darkmode-bg">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            <tr>
                                <td style="padding: 20px; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555;">
                                    <p style="margin: 0;">Utilizar la plataforma WeLoveRacket es realmente económico. Primeramente porque el primer mes no vas a pagar nada. Así podrás decidir
                                        si te quedas con nosotros o de lo contratio usar otra forma de gestionar el club. Lo segundo porque diposnemos de dos planes que seguro de alguno se adapta 
                                        a tus necesidades.
                                    </p>
                                </td>
                            </tr>
                        </table>
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            <tr>
                                <td valign="top" width="50%">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td style="text-align: left; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555; padding: 10px 10px 0;">
                                                <p style="padding: 10px;" ><b>PLAN BÁSICO</b></p>
                                                <ul style="padding: 0; margin: 0 0 10px 0; list-style-type: disc;">
                                                    <li style="margin:0 0 10px 30px;" class="list-item-first">Gestión de reservas.</li>
                                                    <li style="margin:0 0 10px 30px;">Administración de clubs.</li>
                                                    <li style="margin: 0 0 0 30px;" >Administración socios.</li>
                                                    <li style="margin: 0 0 0 30px;" >Consulta datos del club desde la parte pública.</li>
                                                    <li style="margin: 0 0 0 30px;" class="list-item-last">Posibilidad de reserva automática por los socios.</li>
                                                </ul>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td valign="top" width="50%">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td style="text-align: left; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555; padding: 10px 10px 0;">
                                                <p style="padding: 10px;" ><b>PLAN PREMIUM</b></p>
                                                <ul style="padding: 0; margin: 0 0 10px 0; list-style-type: disc;">
                                                    <li style="margin:0 0 10px 30px;" class="list-item-first">Gestión de reservas.</li>
                                                    <li style="margin:0 0 10px 30px;">Administración de clubs.</li>
                                                    <li style="margin: 0 0 0 30px;" >Administración socios.</li>
                                                    <li style="margin: 0 0 0 30px;" >Consulta datos del club desde la parte pública.</li>
                                                    <li style="margin: 0 0 0 30px;" >Creación ilimitada de ligas.</li>
                                                    <li style="margin: 0 0 0 30px;" >Creación ilimitada de torneos.</li>
                                                    <li style="margin: 0 0 0 30px;" class="list-item-last">Posibilidad de reserva automática por los socios.</li>
                                                </ul>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top" width="50%">
                                    <p style="text-align: left; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555;  text-align: center">
                                        Paga 29.90€/mes
                                    </p>
                                </td>
                                <td valign="top" width="50%">
                                    <p style="text-align: left; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555; text-align: center">
                                        Paga 54.90€/mes
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td >
                                    <p>&nbsp;</p>
                                </td>
                            </tr>
                        </table>
                        <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: auto;">
                            <tr>
                                <td class="button-td button-td-primary" style="border-radius: 4px; background: #7C82C5;">
                                    <a class="button-a button-a-primary" href="https://weloveracket.com/register-club" style="background: #7C82C5; border: 1px solid #000000; font-family: sans-serif; font-size: 15px; line-height: 15px; text-decoration: none; padding: 13px 17px; color: #ffffff; display: block; border-radius: 4px;">REGISTRARME GRATIS </a>
                                </td>
                            </tr>
                            
                        </table>
                        <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: auto; padding: 10px 10px 0;">
                            <tr>
                                <td class="button-td button-td-primary" >
                                    <p>&nbsp;</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!-- 1 Column Text : END -->

            </table>
            
            <!-- Email Body : END -->

             <!-- Email Footer : BEGIN -->
        <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="margin: auto;" class="footer">
	        <tr>
	            <td style="padding: 20px; font-family: sans-serif; font-size: 12px; line-height: 15px; text-align: center; color: #ffffff;">
	                <webversion style="color: #ffffff; text-decoration: underline; font-weight: bold;">Visitar <a style="color: #ffffff" href="https://weloveracket.com/club/tournaments/view/hdye6">WeLoveRacket</a> </webversion>
	                <br><br>
	                We Love Racket<br><span class="unstyle-auto-detected-links">C/ José Rector Vida Soria Nº8 A2<br>626 804 645</span>
	                <br><br>
	                <unsubscribe style="color: #ffffff; text-decoration: underline;"><a style="color: #ffffff" href="https://weloveracket.com/club/tournaments/view/hdye6">We Love Racket</a></unsubscribe>
	            </td>
	        </tr>
	    </table>
	    <!-- Email Footer : END -->

            <!--[if mso]>
            </td>
            </tr>
            </table>
            <![endif]-->
        </div>

        <!-- Full Bleed Background Section : BEGIN -->
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #709f2b;" class="darkmode-fullbleed-bg">
	        <tr>
	            <td>
	                <div align="center" style=" margin: auto;" class="email-container">
	                    <!--[if mso]>
	                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" align="center">
	                    <tr>
	                    <td>
	                    <![endif]-->
	                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
	                        <tr>
	                            <td style="padding: 20px; text-align: left; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #ffffff;">
	                                <p style="margin: 0; text-align: justify;">La información contenida en este correo electrónico y, en su caso, en los documentos adjuntos, es información privilegiada para uso exclusivo de las 
                                        personas a las que se dirige. Se ruega que, en caso de no ser el destinatario de este correo, se notifique al remitente y se proceda a su eliminación. Cualquier uso indebido de dicha 
                                        información será bajo su responsabilidad. Le informamos que sus datos están siendo tratados por WeLoveRacket, para la gestión de relaciones comerciales 
                                        y administrativas, así como para el mantenimiento de comunicaciones electrónicas. No se prevén cesiones de sus datos, salvo que exista una obligación 
                                        legal. Podrá contactar con nuestro Delegado de Protección de Datos, así como ejercitar sus derechos de acceso, rectificación, supresión, oposición, 
                                        limitación y portabilidad en Rector José Vida Soria, o a través del email support@weloveracket.com, indicando en su caso, el derecho que desea ejercer 
                                        y adjuntando documento que acredite su identidad. También podrá presentar una reclamación ante la Agencia Española de Protección de Datos.</p>
	                            </td>
	                        </tr>
	                    </table>
	                    <!--[if mso]>
	                    </td>
	                    </tr>
	                    </table>
	                    <![endif]-->
	                </div>
	            </td>
	        </tr>
	    </table>
        <!-- Full Bleed Background Section : END -->

    <!--[if mso | IE]>
    </td>
    </tr>
    </table>
    <![endif]-->
    </center>
</body>
</html>