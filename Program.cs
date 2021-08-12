using System;
using OpenQA.Selenium;
using OpenQA.Selenium.Chrome;
using OpenQA.Selenium.Support.UI;




//Versão 9.0

namespace ConsoleLances
{
    class Program
    {
        // Id da franquia cujas mensagens automáticas serão enviadas
        private static string cod_franquia = "FSE0013";

        // Nome da franquia
        private static string nome_franquia = "Franquia Brasília Águas Claras";


        // 1 - Mensagens enviadas em ordem CRESCENTE   2 - Mensagens enviadas em ordem DECRESCENTE
        private static int ordem = 2;

        // Descrição da ordem de envio das mensagens que será definido abaixo
        private static string descricao_ordem;

        // Determina se o número de um destinatário é inválido (não possui whatsapp ou é número bloqueado)
        private static bool numero_invalido = false;




        /**************************************************************************/
        /*********************** ROTAS QUE GERAM MENSAGENS ************************/
        /**************************************************************************/

        private static string url_super = "https://wpp.gruposuper.com.br/gera_whats.php";




        /**************************************************************************/
        /********************* FUNÇÕES DE ENVIO DE MENSAGEM ***********************/
        /**************************************************************************/

        static void iniciar(IWebDriver driver)
        {
            try
            {
                // Vá para o site do WhatsApp Web
                driver.Navigate().GoToUrl("https://web.whatsapp.com/");
            }
            catch
            {
                iniciar(driver);
            }
        }

        static void esperar_codigo_qr_ser_escaneado(IWebDriver driver)
        {

            // Inicializa ferramenta de espera
            WebDriverWait esperar_10_segundos = new WebDriverWait(driver, TimeSpan.FromSeconds(10));
            
            
            bool codigo_qr_existe;

            bool imagem_perfil_existe;


            // Sai da repetição abaixo quando o usuário escanear o código QR

            do
            {
                try
                {
                    // Tenta selecionar o código QR
                    IWebElement codigo_qr = esperar_10_segundos.Until(d => d.FindElement(By.ClassName("_1N3oL")));


                    // Avise para escanear o código QR
                    Console.WriteLine(DateTime.Now.ToString("HH:mm:ss") + " - Escaneie o código QR.\n");


                    // Espera 4 segundos para escanear o código
                    System.Threading.Thread.Sleep(4000);


                    // Código QR existe
                    codigo_qr_existe = true;


                    // Se o código QR existe, a imagem de perfil não existe
                    imagem_perfil_existe = false;
                }
                // Código QR não existe
                catch (WebDriverTimeoutException)
                {

                    codigo_qr_existe = false;

                    try
                    {
                        // Tenta selecionar o código QR
                        IWebElement imagem_perfil = esperar_10_segundos.Until(d => d.FindElement(By.ClassName("_1G3Wr"))); 

                        // Imagem de perfil existe
                        imagem_perfil_existe = true;

                        // Se a imagem de perfil existe,  o código QR não existe
                        codigo_qr_existe = false;
                    }
                    catch (WebDriverTimeoutException)
                    {
                        try
                        {
                            // Tenta selecionar o código QR
                            IWebElement imagem_perfil = esperar_10_segundos.Until(d => d.FindElement(By.ClassName("_1G3Wr")));

                            // Imagem de perfil existe
                            imagem_perfil_existe = true;

                            // Se a imagem de perfil existe,  o código QR não existe
                            codigo_qr_existe = false;
                        }
                        catch (WebDriverTimeoutException)
                        {
                            imagem_perfil_existe = false;
                        }
                    }
                }
            }
            // Enquanto o código QR existir na página (ou seja, WhatsApp Web não está conectado)
            // Ou a imagem de perfil de usuário não existir (não terminal de carregar a página)
            while (codigo_qr_existe || !imagem_perfil_existe);


            // Log da Etapa
            Console.WriteLine(DateTime.Now.ToString("HH:mm:ss") + " - Código QR escaneado.\n");
        }

        // Checa se há um elemento 'alert' sendo exibido
        static bool alerta_esta_exibindo(IWebDriver driver)
        {
            // Tente
            try
            {
                // Selecione o elemento 'alert'
                IAlert alert = driver.SwitchTo().Alert();

                // Feche o elemento
                alert.Dismiss();

                // Retorne verdadeiro
                return true;
            }
            // Erro
            catch (NoAlertPresentException)
            {
                // Retorne falso
                return false;
            }
        }



        static void enviar_mensagens(IWebDriver driver, string url, string cod_franquia, int ordem)
        {

          

            string id_mensagem;

            do
            {
                // Acessa a página com o link para a mensagem e salva o id da mensagem
                id_mensagem = abrir_mensagem_perfil_exato(driver, url, cod_franquia, ordem);

                if (id_mensagem == "Não há mensagens.")
                {
                    break;
                }

                Console.WriteLine(DateTime.Now.ToString("HH:mm:ss") + " - Página do WhatsApp Web aberta junto com mensagem a enviar.\n");


                enviar_mensagem(driver, url, id_mensagem);
            }
            while (id_mensagem != "Não há mensagens.");
        }


        static string abrir_mensagem_perfil_exato(IWebDriver driver, string url, string cod_franquia, int ordem)
        {

            // Inicializa ferramenta de espera
            WebDriverWait esperar_1_min = new WebDriverWait(driver, TimeSpan.FromSeconds(60));

            
            // Log do ACESSO À PÁGINA
            Console.WriteLine(DateTime.Now.ToString("HH:mm:ss") + " - Acessando página com link da mensagem.\n");

            string id_mensagem = null;

            try
            {
                // Vá para url, passando o id da franquia e a ordem das mensagens como argumentos
                driver.Navigate().GoToUrl(url + "?c=" + cod_franquia + "&ord=" + ordem);

                try
                {
                    /***************************************************************/
                    /************ SELECIONA O LINK DE ENVIO DE MENSAGEM ************/
                    /***************************************************************/


                    // Espera 5 min até selecionar o link da mensagem
                    IWebElement link_enviar_whats = esperar_1_min.Until(d => d.FindElement(By.Id("link-whats")));


                    // Seleciona o texto do link (que contém o id da mensagem)
                    id_mensagem = link_enviar_whats.GetAttribute("text");


                    // Se não houver mais mensagens
                    if (id_mensagem == "Não há mensagens.")
                    {
                        return "Não há mensagens.";
                    }
                    else
                    {

                        // Executor JavaScript
                        IJavaScriptExecutor executor = (IJavaScriptExecutor)driver;

                        // Clica diretamente no botão
                        executor.ExecuteScript("arguments[0].click();", link_enviar_whats);


                        // Log APÓS CLIQUE PARA ABRIR WHATSAPP 
                        Console.WriteLine(DateTime.Now.ToString("HH:mm:ss") + " - Link clicado para enviar mensagem. Abrindo página WhatsApp Web.\n");


                        // Detecta se a págia WhatsApp Web abriu corretamente
                        detectar_pagina_whatsapp_aberta(driver, url, cod_franquia, ordem);


                        // Retorna o id da mensagem
                        return id_mensagem;
                    }
                }
                // Se depois de 5 min o link não carregar
                catch (WebDriverTimeoutException)
                {
                    // Chama a função novamente,
                    // Essencialmente, criando uma RECURSÃO
                    return abrir_mensagem_perfil_exato(driver, url, cod_franquia, ordem);
                }

            }
            catch
            {
                return abrir_mensagem_perfil_exato(driver, url, cod_franquia, ordem);
            }
        }


        static void detectar_pagina_whatsapp_aberta(IWebDriver driver, string url, string cod_franquia, int ordem)
        {

            
            // Inicializa ferramenta de espera
            WebDriverWait esperar_10_segundos = new WebDriverWait(driver, TimeSpan.FromSeconds(10));


            // Declara 'imagem_perfil' (imagem de perfil do usuário) e 'numero_invalido' (div do alerta dizendo que o número é inválido)
            IWebElement imagem_perfil = null, numero_invalido = null;


            // Faça
            do
            {
                // Repita 5 vezes
                for (int i = 0; i <= 5; i++)
                {
                    try
                    {
                        // Espera 5 segundos até selecionar a imagem de perfil
                        imagem_perfil = esperar_10_segundos.Until(d => d.FindElement(By.ClassName("_1G3Wr")));

                        Console.WriteLine("Encontrou imagem_perfil");


                        // Imagem de perfil está sendo exibida, então sai da repetição
                        break;
                    }
                    // Se não puder selecionar 'imagem_perfil' após 5 segundos
                    catch (WebDriverTimeoutException)
                    {
                        Console.WriteLine("Não conseguiu encontrar imagem_perfil");


                        Console.WriteLine("Tentando número inválido");

                        try
                        {
                            // Espera 5 segundos até selecionar o aviso de número inválido
                            numero_invalido = esperar_10_segundos.Until(d => d.FindElement(By.ClassName("_1HX2v")));

                            Console.WriteLine("Encontrou número inválido");

                            // Aviso de número inválido está sendo exibido, então sai da repetição
                            break;
                        }
                        // Se não puder selecionar o aviso 'numero_invalido' (e nem a imagem de perfil) após 5 segundos
                        catch (WebDriverTimeoutException)
                        {

                            // Se já tiver tentado 5 vezes
                            if (i >= 5)
                            {
                                abrir_mensagem_perfil_exato(driver, url, cod_franquia, ordem);
                            }
                            else
                            {
                                System.Threading.Thread.Sleep(1000);
                            }
                        }
                    }
                }
            }
            // Enquanto 'imagem_perfil' não existir e 'numero_invalido' não existir
            while (imagem_perfil == null && numero_invalido == null);
        }


        static void enviar_mensagem(IWebDriver driver, string url, string id_mensagem)
        {
            // Inicializa ferramenta de espera
            WebDriverWait esperar_10_segundos = new WebDriverWait(driver, TimeSpan.FromSeconds(10));


            Console.WriteLine(DateTime.Now.ToString("HH:mm:ss") + " - Pronto para enviar a mensagem.\n");

            // Declara o botão de enviar mensagem,
            // aviso de número não encontrado,
            // e campo de escrever mensagem
            IWebElement botao_enviar_mensagem = null;

            IWebElement aviso_numero_nao_encontrado = null;

            IWebElement campo_enviar_mensagem = null;


            // Define que o contato NÃO está bloqueado
            bool contato_bloqueado = false;


            /***************************************************************************************/
            /******************** DETERMINA CONTATO SEM WHATSAPP OU BLOQUEADO **********************/
            /***************************************************************************************/
            do
            {
                try
                {
                    // Espera 10 segundos até selecionar a imagem de perfil
                    botao_enviar_mensagem = esperar_10_segundos.Until(d => d.FindElement(By.ClassName("_4sWnG")));
                }
                catch (WebDriverTimeoutException)
                {

                    /**************************************************************************/
                    /******************** DETERMINA CONTATO SEM WHATSAPP **********************/
                    /**************************************************************************/

                    try
                    {

                        aviso_numero_nao_encontrado = esperar_10_segundos.Until(d => d.FindElement(By.ClassName("_3J6wB")));


                        if (aviso_numero_nao_encontrado != null)
                        {
                            // Determina que o número é inválido (não possui whatsapp)
                            numero_invalido = true;

                            Console.WriteLine(DateTime.Now.ToString("HH:mm:ss") + " - (Erro) Contato não possui WhatsApp.\n");


                            registrar_contato_sem_whatsapp(driver, url, id_mensagem);
                        }
                    }
                    catch (WebDriverTimeoutException)
                    {

                        /**************************************************************************/
                        /********************* DETERMINA CONTATO BLOQUEADO ************************/
                        /**************************************************************************/

                        try
                        {
                            campo_enviar_mensagem = esperar_10_segundos.Until(d => d.FindElement(By.ClassName("_31enr")));

                            if (campo_enviar_mensagem != null)
                            {

                                // Seleciona o texto do campo
                                string texto = campo_enviar_mensagem.Text.ToString();

                                if (texto.Contains("contato bloqueado"))
                                {
                                    // Determina número inválido
                                    numero_invalido = true;

                                    // Determina contato bloqueado
                                    contato_bloqueado = true;

                                    Console.WriteLine(DateTime.Now.ToString("HH:mm:ss") + " - (Erro) Contato bloqueado.\n");

                                    // Registra que o contato está bloqueado
                                    driver.Navigate().GoToUrl(url + "?id_bloq=" + id_mensagem);
                                }
                            }
                        }
                        catch (WebDriverTimeoutException)
                        {

                        }
                    }
                }
            }
            // Enquanto o botão de enviar não existir
            // Enquanto o número estiver disponível
            // Enquanto o contato não estiver bloqueado
            while (botao_enviar_mensagem == null
                   && aviso_numero_nao_encontrado == null
                   && contato_bloqueado == false);








            if (botao_enviar_mensagem != null)
            {
                Console.WriteLine("Saiu da repetição pois botão de enviar mensagem existe.\n");
            }

            if (aviso_numero_nao_encontrado != null)
            {
                Console.WriteLine("Saiu da repetição pois o número não possui WhatsApp.\n");
            }

            if (contato_bloqueado != false)
            {
                Console.WriteLine("Saiu da repetição pois o contato está bloqueado.\n");
            }






            /**************************************************************************/
            /********************* CLICA PARA ENVIAR MENSAGEM *************************/
            /**************************************************************************/

            // Se o número for válido e se o botão de enviar existir
            if (numero_invalido == false || botao_enviar_mensagem != null)
            {

                Console.WriteLine(DateTime.Now.ToString("HH:mm:ss") + " - Prestes a clicar em enviar mensagem.\n");



                clicar_botao_enviar_mensagem(botao_enviar_mensagem, driver);



                Console.WriteLine(DateTime.Now.ToString("HH:mm:ss") + " - Botão para enviar mensagem clicado.\n");


                registrar_envio_de_mensagem(driver, url, id_mensagem);

            }
        }


        static void clicar_botao_enviar_mensagem(IWebElement botao_enviar_mensagem, IWebDriver driver)
        {

            // Inicializa ferramenta de espera
            WebDriverWait esperar_1_min = new WebDriverWait(driver, TimeSpan.FromSeconds(60 * 1));



            try
            {
                // Espera 1 minuto até que o botão seja clicável
                esperar_1_min.Until(SeleniumExtras.WaitHelpers.ExpectedConditions.ElementToBeClickable(botao_enviar_mensagem));

                // Executor JavaScript
                IJavaScriptExecutor executor = (IJavaScriptExecutor)driver;

                // Clica diretamente no botão
                executor.ExecuteScript("arguments[0].click();", botao_enviar_mensagem);

                // Para por 1 segundos
                System.Threading.Thread.Sleep(1000);
            }
            catch (WebDriverTimeoutException)
            {
                // Chama a função novamente caso dê erro de espera (1 minuto)
                clicar_botao_enviar_mensagem(botao_enviar_mensagem, driver);
            }

        }


        static void registrar_envio_de_mensagem(IWebDriver driver, string url, string id_mensagem)
        {

            

            try
            {
                driver.Navigate().GoToUrl(url + "?id_enviado=" + id_mensagem);

                // ?????????????????????????????

                var mensagem_nao_enviada = alerta_esta_exibindo(driver);

                // Se o alerta de mensagem não enviada estiver sendo exibido
                if (mensagem_nao_enviada == true)
                {
                    do
                    {
                        Console.WriteLine(DateTime.Now.ToString("HH:mm:ss") + " - esperando enviar...\n");

                        // Visita a página para registrar que a mensagem foi enviada
                        driver.Navigate().GoToUrl(url + "?id_enviado=" + id_mensagem);

                        // Determina se alerta está sendo exibido
                        mensagem_nao_enviada = alerta_esta_exibindo(driver);
                    }
                    // Enquanto a mensagem não for enviada
                    while (mensagem_nao_enviada == true);
                }
            }
            catch
            {
                registrar_envio_de_mensagem(driver, url, id_mensagem);
            }
        }


        static void registrar_contato_sem_whatsapp(IWebDriver driver, string url, string id_mensagem)
        {
            try
            {
                // Registra que o destinatário não possui WhatsApp
                driver.Navigate().GoToUrl(url + "?id_nao_possui_wpp=" + id_mensagem);
            }
            catch
            {
                registrar_contato_sem_whatsapp(driver, url, id_mensagem);
            }
        }


        /**************************************************************************/
        /**************************          MAIN         *************************/
        /**************************************************************************/

        static void Main(string[] args)
        {

            /**************************************************************************/
            /****************************** CONFIGURAÇÕES *****************************/
            /**************************************************************************/

            // Define a descrição da ordem de envio das mensagens
            if (ordem == 1) descricao_ordem = "CRESCENTE";
            else descricao_ordem = "DECRESCENTE";


            Console.WriteLine("Nome do usuário: " + System.Security.Principal.WindowsIdentity.GetCurrent().Name);

            Console.WriteLine("Nome do usuário (ambiente): " + Environment.UserName);


            // Log de INÍCIO
            Console.WriteLine(DateTime.Now.ToString("HH:mm:ss") + " - Iniciando SuperWhats para " + nome_franquia + ".\n");
            
            Console.WriteLine(DateTime.Now.ToString("HH:mm:ss") + " - Código da franquia " + cod_franquia + ".\n");


            // Log da ORDEM DE ENVIO
            Console.WriteLine(DateTime.Now.ToString("HH:mm:ss") + " - Mensagens serão enviadas em ordem " + descricao_ordem + ".\n");

            // Chrome driver iniciado

            ChromeOptions opcoes = new ChromeOptions();

            opcoes.AddArgument("--user-data-dir=C:/Users/" + Environment.UserName + "/AppData/Local/Google/Chrome/User Data/Profile 1");

            opcoes.AddArgument("--disable-extensions");

            IWebDriver driver = new ChromeDriver(opcoes);


            /**************************************************************************/
            /************************** INICIALIZAÇÃO *********************************/
            /**************************************************************************/

            // Inicia abrindo a página do WhatsApp Web
            iniciar(driver);

            // Espera até que o código QR seja escaneado
            esperar_codigo_qr_ser_escaneado(driver);

            Console.WriteLine(DateTime.Now.ToString("HH:mm:ss") + " - Página do WhatsApp Web carregada.\n");



            /**************************************************************************/
            /** REPETIÇÃO QUE ENVIA MENSAGENS ATÉ QUE NÃO HAJA MENSAGENS PARA ENVIAR **/
            /**************************************************************************/

            Console.WriteLine("\n\nIniciando envio de mensagens\n\n");
            enviar_mensagens(driver, url_super, cod_franquia, ordem);



            /**************************************************************************/
            /**************************** ENCERRA O PROGRAMA **************************/
            /**************************************************************************/

            // Mensagem final
            Console.WriteLine("Todas as mensagens enviadas. Aperte Enter para fechar o programa.\n");

            // Aguarda o usuário apertar Enter
            Console.ReadLine();

            // Encerra o driver
            driver.Quit();

        }
    }
}
