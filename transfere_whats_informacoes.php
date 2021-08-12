<?

if ($_SERVER['SERVER_PORT'] == 80)
{
	header("location:https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]); 
}


include_once ('../../inicializa.php');


// Se 'f' estiver definido em $_GET
if ($_GET['f'])
{
    // Só transfere mensagens da franquia 'f'
	$and_unica = " AND id = '".$_GET['f']."' ";
}



// Seleciona a(s) franquia(s)
$consulta_franquia = $database->query("SELECT id,
                                              franquia,
                                              cod_zap
                              FROM franquias
                              WHERE NOT ISNULL(cod_zap)
                              $and_unica"
);


/************************************************************************************************************/
/*********************************************** INFORMAÇÕES ************************************************/
/************************************************************************************************************/


echo "<p>Iniciando</p>";


// Enquanto houver resultados
while ($resultado_franquia = $database->fetch_object($consulta_franquia))
{
	echo "<h1>Transferindo Whats de " . utf8_encode($resultado_franquia->franquia) . "</h1>";

	echo "<p>Buscando Infos de " . utf8_encode($resultado_franquia->franquia) . "</p>";

    $consulta_informacoes = $database->query("SELECT UPPER(e.nome) AS nome,
                                                     e.tel1,
                                                     e.tel1_tipo,
                                                     e.tel2,
                                                     e.tel2_tipo,
                                                     e.tel3,
                                                     e.tel3_tipo,
                                                     inf.idEstudante,
                                                     inf.id_usuario,
                                                     inf.Atendido,
                                                     inf.tipo_informacao,
                                                     inf.motivo,
                                                     inf.id_informacoes 

                                                FROM informacoes inf

                                                JOIN estagiarios e ON e.id = inf.idEstudante

                                                WHERE inf.tipo_informacao in ('8', '6')

                                                AND IF(inf.tipo_informacao = '8', inf.Atendido = '70', inf.Atendido = '60' AND id_usuario > 1)
                                                AND (tel1_tipo = 'Celular' || tel2_tipo = 'Celular' || tel3_tipo = 'Celular')
                                                AND inf.id_franquia IN ($resultado_franquia->id)
                                                AND inf.id_franquia IS NOT NULL
                                                AND inf.id_franquia != 0"
    );


    // Se houver resultados na pesquisa
	if ($database->num_rows($consulta_informacoes))
    {
        // Percorre cada resultado
		while ($resultado_informacoes = $database->fetch_object($consulta_informacoes))
        {

            // Seleciona o id do estudante
			$idEstudante = $resultado_informacoes->idEstudante;


			$tel = "";


			if(!strcmp($resultado_informacoes->tel1_tipo,"Celular"))
            {
                $tel = $resultado_informacoes->tel1;
            } 
			elseif(!strcmp($resultado_informacoes->tel2_tipo,"Celular"))
            {
                $tel = $resultado_informacoes->tel2;
            }
			elseif(!strcmp($resultado_informacoes->tel3_tipo,"Celular"))
            {
                $tel = $resultado_informacoes->tel3;
            }

			$telefone = str_replace('-', '', str_replace(' ', '', str_replace('(', '', str_replace(')', '', $tel))));
			
            // SE POSSUIR CELULAR
			if($telefone != "")
            { 
				// SE O TELEFONE NAO TIVER O NONO DIGITO 

				if(strlen($telefone) == 10)
                {
					$antes = substr($telefone, 0, 2);

					$depois = substr($telefone, 2);

					$telefone = $antes . "9" . $depois;
				}

				$msg = ("Super Estágios Informa:") . utf8_encode($resultado_informacoes->motivo);

				$mensagem = rawurlencode($msg);

				$tipo_mensagem = 3;

				$array_valores = array(
					'auth' => "Wpp-GrupoSuper",
					'metodo' => "inserir_mensagem",
					'CodigoZap' => $resultado_franquia->cod_zap,
					'Mensagem' => $msg,
					'Destinatario' => $telefone,
					'tipo_mensagem' => $tipo_mensagem,
					'id_origem' => $resultado_informacoes->id_informacoes,
				);

				$retorno = comunica_api($array_valores,"https://wpp.gruposuper.com.br/api/index.php");

				var_dump($retorno);

                $atualiza = $database->query("UPDATE informacoes
                                              SET Atendido = '71'
                                              WHERE id_informacoes = '$resultado_informacoes->id_informacoes'"
                );
			}
            else
            {
                $atualiza = $database->query("UPDATE informacoes
                                              SET Atendido = '72'
                                              WHERE id_informacoes = '$resultado_informacoes->id_informacoes'"
                );
			}
		}
		echo "<p>Mensagens de INFORMAÇÕES transferidas com sucesso.</p>";
	}
    else
    {
		echo "<p>Nenhuma mensagem de INFORMAÇÕES para ser transferida.</p>";
	}
}

echo "<p id='mensagem-fim'>Fim da Transferência</p>";
?>