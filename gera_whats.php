<?

include_once ('includes/inicializa.php');


date_default_timezone_set('America/Sao_Paulo');


// Código do programa referente à franquia
$cod = $_GET['c'];


// Ordem das mensagens (Ascendente ou Decrescente)
$ordem = $_GET['ord'];



// Se ordem for CRESCENTE
if ($ordem == 1)
{
    $ordem = "ORDER BY data_insert ASC";
}
// Se ordem for DECRESCENTE
elseif ($ordem == 2)
{
    $ordem = "ORDER BY data_insert DESC";
}


$current_php_time = date("Y-m-d H:i:s");




if ($_GET['id_enviado'])
{
	$database->query("UPDATE mensagens
                      SET status = 1,
                          data_envio = '$current_php_time'
                      WHERE id = '".$_GET['id_enviado']."'"
    );
}
elseif ($_GET['id_nao_possui_wpp'])
{
	$update = $database->query("UPDATE mensagens
                                SET status = 2
                                WHERE id = '".$_GET['id_nao_possui_wpp']."'"
    );

	$query = $database->query("SELECT id_origem,
                                      p.cod_zap
                               FROM mensagens m
                               JOIN programa p ON p.id = m.id_programa
                               WHERE m.id = '".$_GET['id_nao_possui_wpp']."'
                               AND NOT ISNULL(id_origem)"
    );
	
    if ($database->num_rows($query))
    {
		$dados = $database->fetch_object($query);

		if (substr($dados->cod_zap,0,3) == "FSE")
        {
			$array_valores = array('auth' => "SuperEstagios-MelhorMaiorAgenciaEstagios",
                                   'metodo' => "atualiza_status_wpp",
                                   'id_informacao' => $dados->id_origem
            );

			$url = "https://www.superestagios.com.br/api/index.php";

			comunica_api($array_valores,$url);
		}
        else
        {
			echo "-";
		}
	}
}
elseif ($_GET['id_bloq'])
{
	$update = $database->query("UPDATE mensagens
                                SET status = 3
                                WHERE id = '".$_GET['id_bloq']."'"
    );
}
// Rota mais comumente executada
else
{
    // Selecione o id do programa SuperWhats
    // cujo código zap sejá o código enviado pelo pedido GET
    // e cujo campo envio seja 1 (o que significa que o programa tem mensagens para enviar)
	$query_envia = $database->query("SELECT id
                                     FROM programa
                                     WHERE cod_zap = '$cod'
                                     AND envio = 1"
    );

    // Se o programa não existir ou não houver mensagens para serem enviadas
	if (!$database->num_rows($query_envia))
    {
        // Mensagem exibida quando não há mais mensagens para serem enviadas
		echo "<h1>Nenhuma mensagem a ser disparada no momento</h1>";
		exit();
	}
    // Senão
    else
    {
        // Selecione o id do programa
		$cod = $database->fetch_object($query_envia)->id;
	}
	
    // Consulte mensagens
    // cujo status seja 0
    // e cujo código do programa seja o código do SuperWhats da franquia em questão
    // Ordene por data de inserção no banco de dados
    // em ordem decrescente
    // Limite os resultados em 1
	$query = $database->query("SELECT id,
                                      mensagem,
                                      destinatario,
                                      tipo,
                                      id_origem,
                                      data_insert
                               FROM mensagens
                               WHERE status = 0
                               AND id_programa = '$cod'
                               
                               $ordem

                               LIMIT 1"
    );

    // Se a consulta em mensagens tiver resultados
	if ($database->num_rows($query))
    {
        // Enquanto a houver resultados
		while ($result = $database->fetch_object($query))
        {	
            // Se o tipo de mensagem for

            // Aniversário
			if ($result->tipo == 6)
            {
				if (substr($result->data_insert, 0, 10) != date('Y-m-d'))
                {
                    // Defina a mensagem como sendo de Entrevista
					$database->query("UPDATE mensagens
                                      SET status = 4
                                      WHERE id = '$result->id'"
                    );

                    //
					$query = $database->query("SELECT id,
                                                      mensagem,
                                                      destinatario,
                                                      tipo,
                                                      id_origem,
                                                      data_insert
                                               FROM mensagens
                                               WHERE status = 0
                                               AND id_programa = '$cod'
                                               
                                               $ordem

                                               DESC LIMIT 1"
                    );

                    // Vá para a próxima iteração da repetição While
					continue;
				}
			}

            // Perfil Exato
			if ($result->tipo == 5)
            {
                // 
				$array_valores = array('auth' => "SuperEstagios-MelhorMaiorAgenciaEstagios",
                                       'metodo' => "checa_mensagem_perfil_exato",
                                       'id_informacao' => $result->id_origem
                );

                // URL do API
				$url = "https://www.superestagios.com.br/api/index.php";

                // Envia o método e os dados para o API
				$r_api = comunica_api($array_valores, $url);

                // Se a consulta não retornar SUCESSO
				if (!$r_api->sucesso)
                {
					$database->query("UPDATE mensagens
                                      SET status = 4
                                      WHERE id = '$result->id'"
                    );
					
                    $retorno_valores = array('auth' => "SuperEstagios-MelhorMaiorAgenciaEstagios",
                                             'metodo' => "mensagem_desconsiderada",
                                             'id_informacao' => $result->id_origem,
                                             'tipo' => $result->tipo
                    );
					
                    // URL do API
                    $url_retorno = "https://www.superestagios.com.br/api/index.php";
					
                    $r_retorno = comunica_api($retorno_valores, $url_retorno);
					
                    $query = $database->query("SELECT id,
                                                      mensagem,
                                                      destinatario,
                                                      tipo,
                                                      id_origem,
                                                      data_insert
                                               FROM mensagens
                                               WHERE status = 0
                                               AND id_programa = '$cod'
                                               
                                               $ordem

                                               LIMIT 1"
                    );
                    
					continue;
				}
			}

            // Entrevista
			if ($result->tipo == 4)
            {
                // Define os dados a serem enviados para o API
				$array_valores = array('auth' => "SuperEstagios-MelhorMaiorAgenciaEstagios",
                                       'metodo' => "checa_mensagem_entrevista",
                                       'id_selecao' => $result->id_origem
                );

				// Define a rota do API
                $url = "https://www.superestagios.com.br/api/index.php";

                // Envia o pedido e seleciona o resultado do API
				$r_api = comunica_api($array_valores,$url);

                // Se a consulta não
				if (!$r_api->sucesso)
                {
					$database->query("UPDATE mensagens
                                      SET status = 4
                                      WHERE id = '$result->id'"
                    );

					$retorno_valores = array('auth' => "SuperEstagios-MelhorMaiorAgenciaEstagios",
                                             'metodo' => "mensagem_desconsiderada",
                                             'id_informacao' => $result->id_origem,
                                             'tipo' => $result->tipo
                    );
					
                    $url_retorno = "https://www.superestagios.com.br/api/index.php";

					$r_retorno = comunica_api($retorno_valores,$url_retorno);

					$query = $database->query("SELECT id,
                                                      mensagem,
                                                      destinatario,
                                                      tipo,
                                                      id_origem,
                                                      data_insert
                                               FROM mensagens
                                               WHERE status = 0
                                               AND id_programa = '$cod'

                                               $ordem

                                               LIMIT 1"
                    );

					continue;
				}
			}

            // Remove sinais e pontuação do número de telefone do destinatário
			$telefone = str_replace('-', '', str_replace(' ', '', str_replace('(', '', str_replace(')', '', $result->destinatario))));

            // Se o teleofne não tiver o nono dígito 
            if (strlen($telefone) == 10)
            {
                $antes = substr($telefone, 0, 2);
                $depois = substr($telefone, 2);
                $telefone = $antes . "9" . $depois;
            }

            // Seleciona o conteúdo da mensagem a ser enviada
            $msg = utf8_encode($result->mensagem);

            // Formata mensagem para ser incluída no URL
            $mensagem = rawurlencode($msg);


            
            // Descrição
            echo "<p><h1>Gerando Mensagem</h1></p>";

            // Link que envia $mensagem para $telefone
            echo "<a id='link-whats' href=\"https://web.whatsapp.com/send?phone=55$telefone&text=$mensagem\">$result->id</a>";

            exit();		
		}
	}
    // Senão, se não houver resultados
    else
    {
        // Defina o campo envio do programa como sendo 0
        // Ou seja, o programa não possui mensagens para enviar
		$update = $database->query("UPDATE programa
                                    SET envio = 0
                                    WHERE id = '$cod'"
        );
	}
}

