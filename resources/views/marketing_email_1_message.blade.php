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

        /* What it does: Replaces default bold style. */
        th {
        	font-weight: normal;
        }

        /* What it does: Fixes webkit padding issue. */
        table {
            border-spacing: 0 !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 0 auto !important;
        }

        /* What it does: Prevents Windows 10 Mail from underlining links despite inline CSS. Styles for underlined links should be inline. */
        a {
            text-decoration: none;
        }

        /* What it does: Uses a better rendering method when resizing images in IE. */
        img {
            -ms-interpolation-mode:bicubic;
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

        /* What it does: Prevents Gmail from changing the text color in conversation threads. */
        .im {
            color: inherit !important;
        }

        /* What it does: Prevents Gmail from displaying a download button on large, non-linked images. */
        .a6S {
           display: none !important;
           opacity: 0.01 !important;
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

            .email-container {
                width: 100% !important;
                margin: auto !important;
            }

            /* What it does: Forces table cells into full-width rows. */
            .stack-column,
            .stack-column-center {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                direction: ltr !important;
            }
            /* And center justify these ones. */
            .stack-column-center {
                text-align: center !important;
            }

            /* What it does: Generic utility class for centering. Useful for images, buttons, and nested tables. */
            .center-on-narrow {
                text-align: center !important;
                display: block !important;
                margin-left: auto !important;
                margin-right: auto !important;
                float: none !important;
            }
            table.center-on-narrow {
                display: inline-block !important;
            }

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
              Empieza a utilizar WeLoveRacket para la administración de tu club.
        </div>
        <!-- Visually Hidden Preheader Text : END -->

        <!-- Create white space after the desired preview text so email clients don’t pull other distracting text into the inbox preview. Extend as necessary. -->
        <!-- Preview Text Spacing Hack : BEGIN -->
        <div style="display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all; font-family: sans-serif;">
            &zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;
        </div>
        <!-- Preview Text Spacing Hack : END -->

        <!-- Email Body : BEGIN -->
        <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="margin: auto;" class="email-container">
	        <!-- Email Header : BEGIN -->
             <tr>
                <td style="padding: 20px 0; text-align: center">
              <!--      <img src="https://api.weloveracket.com/assets/img/emails/email-logo.jpg" width="200" height="50" alt="alt_text" border="0" style="height: auto; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555;"> -->
                </td>
            </tr> 
	        <!-- Email Header : END -->

            <!-- Hero Image, Flush : BEGIN -->
            <tr>
                <td style="background-color: #ffffff;" class="darkmode-bg">
                    <img src="https://api.weloveracket.com/assets/img/emails/email-logo.jpg" width="600" height="" alt="alt_text" border="0" style="width: 100%; max-width: 600px; height: auto; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555; margin: auto; display: block;" class="g-img">
                </td>
            </tr>
            <!-- Hero Image, Flush : END -->

            <!-- 1 Column Text + Button : BEGIN -->
            <tr>
                <td style="background-color: #ffffff;" class="darkmode-bg">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                        <tr>
                            <td style="padding: 20px; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555;">
                                <h1 style="margin: 0 0 10px; font-size: 25px; line-height: 30px; color: #333333; font-weight: normal;">¿Quién es WeLoveRacket?</h1>
                                <p style="margin: 0 0 10px;">WeLoveRacket es una aplicación web desarrollada con mucho cariño por personas que aman el pádel y tenis y es utilizada en muchos clubes de diferentes partes del mundo. En ella podrás: </p>
                                <ul style="padding: 0; margin: 0; list-style-type: disc;">
									<li style="margin:0 0 10px 20px;" class="list-item-first">Gestionar las reservas de las pistas (tenis, pádel, pickleball...)</li>
									<li style="margin:0 0 10px 20px;">Crear reservas recurrentes</li>
                                    <li style="margin:0 0 10px 20px;">Los jugadores podrán ver desde la web la <a href="https://weloveracket.com/club/booking/padel/AY5ks/2025-02-13">disponibilidad</a> de las pistas</li>
                                    <li style="margin:0 0 10px 20px;">Administrar socios del club</li>
                                    <li style="margin:0 0 10px 20px;">Gestionar monitores y la reserva de las pistas para impartir sus clases</li>
                                    <li style="margin:0 0 10px 20px;">Ilimitada creación y gestión de ligas. Fácil de administrar y visibles para todo el público. </li>
									<li style="margin: 0 0 0 20px;" class="list-item-last">Crear ilimitados número de torneos (cuadro con cuadro de consolación, liguilla con playoffs, liga...) con una fácil e intuitiva administración. Visible para el público general</li>
								</ul>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 0 20px 20px;">
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

                    </table>
                </td>
            </tr>
            <!-- 1 Column Text + Button : END -->

            <!-- Background Image with Text : BEGIN -->
            <tr>
                <!-- Bulletproof Background Images c/o https://backgrounds.cm -->
                <td valign="middle" style="text-align: center; background-image: url('https://via.placeholder.com/600x230/7C82C5/666666'); background-color: #7C82C5; background-position: center center !important; background-size: cover !important;">
	                <!--[if gte mso 9]>
	                <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:600px;height:175px; background-position: center center !important;">
	                <v:fill type="tile" src="https://via.placeholder.com/600x230/7C82C5/666666" color="#7C82C5" />
	                <v:textbox inset="0,0,0,0">
	                <![endif]-->
	                <div>
	                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
	                        <tr>
	                            <td valign="middle" style="text-align: center; padding: 40px; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #ffffff;">
	                                <p style="margin: 0;">Puedes utilizar todas las funcionalidades de WeLoveRacket totalmente gratis y sin compromiso durante 4 semanas. No hace falta que introduzcas datos de tu tarjeta o paypal 
                                        para acceder a tu mes gratis. Si pasado ese mes no estás contento con nosotros podrás darte de baja con tan sólo un click.
                                    </p>
	                            </td>
	                        </tr>
	                    </table>
	                </div>
	                <!--[if gte mso 9]>
	                </v:textbox>
	                </v:rect>
	                <![endif]-->
	            </td>
	        </tr>
	        <!-- Background Image with Text : END -->

	        <!-- 2 Even Columns : BEGIN -->
	        <tr>
	            <td style="padding: 10px; background-color: #ffffff;" class="darkmode-bg">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td valign="middle" style="text-align: center; padding: 40px; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555;">
                                <p style="margin: 0;"><b>¿QUÉ PODRÁS HACER?</b>
                                </p>
                            </td>
                        </tr>
                    </table>
	                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
	                    <tr>
	                        <!-- Column : BEGIN -->
	                        <th valign="top" width="33.33%" class="stack-column-center">
	                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
	                                <tr>
	                                    <td style="padding: 10px; text-align: center">
	                                        <img src="https://api.weloveracket.com/assets/img/emails/booking.jpg" width="270" height="" alt="alt_text" border="0" style="width: 100%; max-width: 270px; height: auto; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555;">
	                                    </td>
	                                </tr>
	                                <tr>
	                                    <td style="font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555; padding: 0 10px 10px; text-align: left;" class="center-on-narrow">
	                                        <p style="margin: 0;">Crear y/o cancelar reservas de pistas de manera fácil. Te mostraremos las horas disponibles de cada pista para que puedas realizar las reservas.</p>
	                                    </td>
	                                </tr>
	                            </table>
	                        </th>
	                        <!-- Column : END -->
                             <!-- Column : BEGIN -->
	                        <th valign="top" width="33.33%" class="stack-column-center">
	                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
	                                <tr>
	                                    <td style="padding: 10px; text-align: center">
	                                        <img src="https://api.weloveracket.com/assets/img/emails/all_booking.jpg" width="170" height="" alt="alt_text" border="0" style="width: 100%; max-width: 170px; height: auto; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555;">
	                                    </td>
	                                </tr>
	                                <tr>
	                                    <td style="font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555; padding: 0 10px 10px; text-align: left;" class="center-on-narrow">
	                                        <p style="margin: 0;">Visualizar de forma rápida y sencilla qué pistas, horas y a quién están las reservas del día de hoy o moverte por el calendario por otros días.</p>
	                                    </td>
	                                </tr>
	                            </table>
	                        </th>
	                        <!-- Column : END -->
	                        <!-- Column : BEGIN -->
	                        <th valign="top" width="33.33%" class="stack-column-center">
	                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
	                                <tr>
	                                    <td style="padding: 10px; text-align: center">
	                                        <img src="https://api.weloveracket.com/assets/img/emails/recurrent.jpg" width="270" height="" alt="alt_text" border="0" style="width: 100%; max-width: 270px; height: auto; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555;">
	                                    </td>
	                                </tr>
	                                <tr>
	                                    <td style="font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555; padding: 0 10px 10px; text-align: left;" class="center-on-narrow">
	                                        <p style="margin: 0;">¿María quiere una reserva de la pista 3 todos los viernes de 18 a 19:30 hasta fin de año? Tranquilo, tenemos la opción de reservas recurrente.</p>
	                                    </td>
	                                </tr>
	                            </table>
	                        </th>
	                        <!-- Column : END -->
	                    </tr>
	                </table>
	            </td>
	        </tr>
	        <!-- 2 Even Columns : END -->

	        <!-- 3 Even Columns : BEGIN -->
	        <tr>
	            <td style="padding: 10px; background-color: #ffffff;" class="darkmode-bg">
	                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
	                    <tr>
	                        <!-- Column : BEGIN -->
	                        <th valign="top" width="50%" class="stack-column-center">
	                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
	                                <!-- <tr>
	                                    <td style="padding: 10px; text-align: center">
	                                        <img src="https://via.placeholder.com/170" width="170" height="" alt="alt_text" border="0" style="width: 100%; max-width: 170px; height: auto; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555;">
	                                    </td>
	                                </tr> -->
	                                <tr>
	                                    <td style="font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555; padding: 0 10px 10px; text-align: left;" class="center-on-narrow">
	                                        <p style="padding: 10px; text-align: center">SOCIOS</p>
                                            <p style="margin: 0;">Administra tus socios con una cuota mensual o gratis y que se aprovechen de las ventajas de serlo. Podrán reservas pistas ellos mismo siempre y cuando el club habilite esta opción y todo eso sin coste añadido.</p>
	                                    </td>
	                                </tr>
	                            </table>
	                        </th>
	                        <!-- Column : END -->
	                        
	                        <!-- Column : BEGIN -->
	                        <th valign="top" width="50%" class="stack-column-center">
	                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
	                                <!-- <tr>
	                                    <td style="padding: 10px; text-align: center">
	                                        <img src="https://via.placeholder.com/170" width="170" height="" alt="alt_text" border="0" style="width: 100%; max-width: 170px; height: auto; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555;">
	                                    </td>
	                                </tr> -->
	                                <tr>
	                                    <td style="font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555; padding: 0 10px 10px; text-align: left;" class="center-on-narrow">
	                                        <p style="padding: 10px; text-align: center">MONITORES</p>
                                            <p style="margin: 0;">¡Qué lio con las clases! Te damos la opción de dar de alta a tus monitores y reservas los días y horas que imparten clases para ese tiempo en la pista.</p>
	                                    </td>
	                                </tr>
	                            </table>
	                        </th>
	                        <!-- Column : END -->
	                    </tr>
	                </table>
	            </td>
	        </tr>
	        <!-- 3 Even Columns : END -->

	        <!-- Thumbnail Left, Text Right : BEGIN -->
	        <tr>
	            <td dir="ltr" width="100%" style="padding: 10px; background-color: #ffffff;" class="darkmode-bg">
	                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
	                    <tr>
	                        <!-- Column : BEGIN -->
	                        <th width="33.33%" class="stack-column-center">
	                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
	                                <tr>
	                                    <td dir="ltr" valign="top" style="padding: 0 10px;">
	                                        <img src="https://api.weloveracket.com/assets/img/emails/league.jpg" width="170" height="170" alt="alt_text" border="0" class="center-on-narrow" style="height: auto; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555;">
	                                    </td>
	                                </tr>
	                            </table>
	                        </th>
	                        <!-- Column : END -->
	                        <!-- Column : BEGIN -->
	                        <th width="66.66%" class="stack-column-center">
	                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
	                                <tr>
	                                    <td dir="ltr" valign="top" style="font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555; padding: 10px; text-align: left;" class="center-on-narrow">
	                                        <h2 style="margin: 0 0 10px 0; font-family: sans-serif; font-size: 18px; line-height: 22px; color: #333333; font-weight: bold;">Crea las ligas de tu club</h2>
	                                        <p style="margin: 0 0 10px 0;">Desde nuestra sencilla plataforma pordrás crear y configurar tantas ligas como desees. Será cuestión de segundos introducir las parejas para cada categoría de la liga. 
                                                Una vez creadas las categorías y sus respectivas parejas con un sólo click se generarán las jornadas con sus enfrentamientos. Toda persona, jugador o no, podrá acceder tanto a la clasificación,
                                                resultados o estadística de las parejas. <a href="https://weloveracket.com/club/leagues/view/2">Ver más...</a>
                                            </p>
	                                        <!-- Button : BEGIN -->
	                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" class="center-on-narrow" style="float:left;">
	                                            <tr>
		                                            <td class="button-td button-td-primary" style="border-radius: 4px; background: #7C82C5;">
														<a class="button-a button-a-primary" href="https://weloveracket.com/register-club" style="background: #7C82C5; border: 1px solid #000000; font-family: sans-serif; font-size: 15px; line-height: 15px; text-decoration: none; padding: 13px 17px; color: #ffffff; display: block; border-radius: 4px;">DARME DE ALTA</a>
													</td>
	                                          </tr>
	                                      </table>
	                                      <!-- Button : END -->
	                                    </td>
	                                </tr>
	                            </table>
	                        </th>
	                        <!-- Column : END -->
	                    </tr>
	                </table>
	            </td>
	        </tr>
	        <!-- Thumbnail Left, Text Right : END -->

	        <!-- Thumbnail Right, Text Left : BEGIN -->
	        <tr>
	            <td dir="rtl" width="100%" style="padding: 10px; background-color: #ffffff;" class="darkmode-bg">
	                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
	                    <tr>
	                        <!-- Column : BEGIN -->
	                        <th width="33.33%" class="stack-column-center">
	                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
	                                <tr>
	                                    <td dir="ltr" valign="top" style="padding: 0 10px;">
	                                        <img src="https://api.weloveracket.com/assets/img/emails/tournament.jpg" width="170" height="170" alt="alt_text" border="0" class="center-on-narrow" style="height: auto; background: #dddddd; font-family: sans-serif; font-size: 15px; line-height: 15px; color: #555555;">
	                                    </td>
	                                </tr>
	                            </table>
	                        </th>
	                        <!-- Column : END -->
	                        <!-- Column : BEGIN -->
	                        <th width="66.66%" class="stack-column-center">
	                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
	                                <tr>
	                                    <td dir="ltr" valign="top" style="font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555; padding: 10px; text-align: left;" class="center-on-narrow">
	                                        <h2 style="margin: 0 0 10px 0; font-family: sans-serif; font-size: 18px; line-height: 22px; color: #333333; font-weight: bold;">Tus torneos</h2>
	                                        <p style="margin: 0 0 10px 0;">Podrás crear todos los torneos que quieras totalmente gratis tanto para ti como para los jugadores. 
                                                Podrás introducir las diferentes categorías y en función del número de parejas de cada categoría podrás definir el tipo de campeonato para la categoría.
                                                 Podrás elegir entre: un único cuadro, cuadro + cuadro de consolación, dos liguillas con playoffs, liguilla con ida y vuelta o liga normal. <a href="https://weloveracket.com/club/tournaments/view/hdye6">Ver torneo</a></p>
	                                        <!-- Button : BEGIN -->
	                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" class="center-on-narrow" style="float:left;">
	                                            <tr>
		                                            <td class="button-td button-td-primary" style="border-radius: 4px; background: #7C82C5;">
														<a class="button-a button-a-primary" href="https://weloveracket.com/register-club" style="background: #7C82C5; border: 1px solid #000000; font-family: sans-serif; font-size: 15px; line-height: 15px; text-decoration: none; padding: 13px 17px; color: #ffffff; display: block; border-radius: 4px;">QUIERO MI MES GRATIS</a>
													</td>
	                                            </tr>
	                                        </table>
	                                        <!-- Button : END -->
	                                    </td>
	                                </tr>
	                            </table>
	                        </th>
	                        <!-- Column : END -->
	                    </tr>
	                </table>
	            </td>
	        </tr>
	        <!-- Thumbnail Right, Text Left : END -->

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
	                            <p style="margin: 0;">¿Te hemos convencido? <b>¡Bienvenido a bordo!</b> <br> 
                                Como comprenderás todo tiene un precio y nosotros sabemos que los clubs pequeños siempre andáis apurados. Tenemos dos tipos de
                                planes para que escojas el que más se amolde a tus necesidades. Está el plan básico que incluye todas las opciones de reservas de pistas, socios y monitores con un coste 
                                de 29.90€/mes. Por 54.90€ tienes el plan premium el cual incluye lo que tiene el plan básico más acceso a la creación y administración tanto de ligas como de torneos. 
                                </p>
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