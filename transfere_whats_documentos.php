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


echo "<p>Iniciando</p>";


/************************************************************************************************************/
/************************************************ DOCS ******************************************************/
/************************************************************************************************************/


// Enquanto houver resultados
while ($resultado_franquia = $database->fetch_object($consulta_franquia))
{
	echo "<h1>Transferindo Whats de " . utf8_encode($resultado_franquia->franquia) . "</h1>";

	echo "<p>Buscando Docs de " . utf8_encode($resultado_franquia->franquia) . "</p>";

	$consulta_documentos = "SELECT UPPER(e.nome) AS nome,
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
                          inf.id_informacoes,
                          tc.empresa AS id_empresa,
                          e.auth 

	               FROM informacoes inf
	               JOIN estagiarios e ON e.id = inf.idEstudante
	               JOIN termo_compromisso tc ON e.id = tc.idEstudante

	               WHERE inf.tipo_informacao = '6'
                   AND inf.Atendido = '60'
                   AND inf.id_usuario = 1
	               AND inf.id_franquia IN ($resultado_franquia->id)
                   AND inf.id_franquia IS NOT NULL
                   AND inf.id_franquia != 0
	               GROUP BY idEstudante";

	$docs = $database->query($consulta_documentos);

	if ($database->num_rows($docs))
    {

		$primeiro_include_super_whats = true;

		while ($resultDocs = $database->fetch_object($docs))
        {

			$retorno_include_super_whats = null;				

			$include_super_whats_id_estagiario = $resultDocs->idEstudante;

			$include_super_whats_id_empresa = $resultDocs->id_empresa;

			echo "+";

			include('/home/cpsuper/public_html/includes/emails/crontab/doc902.php');

			echo "-";


			if($retorno_include_super_whats != null)
            {
				$q_infos_antigos = $database->query("SELECT inf.id_informacoes
                                                     FROM informacoes inf
                                                     WHERE inf.tipo_informacao = '6' AND inf.Atendido = '60'						
                                                     AND inf.idEstudante = '$resultDocs->idEstudante'");

				$tot_infos_antigos = $database->num_rows($q_infos_antigos);

				$cont = 0;

				while ($r_infos_antigos = $database->fetch_object($q_infos_antigos)){

					$cont++;

					if($cont == $tot_infos_antigos)
                    {
						$resultDocs->id_informacoes = $r_infos_antigos->id_informacoes;

						$database->query("UPDATE informacoes
                                          SET motivo = '$retorno_include_super_whats', data = now()
                                          WHERE id_informacoes = '$r_infos_antigos->id_informacoes'");							

					}
                    else
                    {
						$database->query("UPDATE informacoes
                                          SET Atendido = 77
                                          WHERE id_informacoes = '$r_infos_antigos->id_informacoes'");

					}//Fim do else total antigos
				}//Fim do While infos antigas

				$resultDocs->motivo = $retorno_include_super_whats;					

				$tel = "";

				if(!strcmp($resultDocs->tel1_tipo,"Celular"))
                {
                    $tel = $resultDocs->tel1;
                } 
				elseif(!strcmp($resultDocs->tel2_tipo,"Celular"))
                {
                    $tel = $resultDocs->tel2;
                } 
				elseif(!strcmp($resultDocs->tel3_tipo,"Celular"))
                {
                    $tel = $resultDocs->tel3;
                }

				$telefone = str_replace('-', '', str_replace(' ', '', str_replace('(', '', str_replace(')', '', $tel))));

                // SE POSSUIR CELULAR
				if($telefone != "")
                { 

					//SE O TELEFONE NAO TIVER O NONO DIGITO 
					if(strlen($telefone)==10)
                    {

						$antes = substr($telefone, 0, 2);

						$depois = substr($telefone, 2);

						$telefone = $antes . "9" . $depois;

					} // Fim do Telefone com 10 dígitos

					$link = 'https://www.superestagios.com.br/wpp.php?a='.base64_encode($resultDocs->auth).'&u='.base64_encode($resultDocs->idEstudante).'&t=4&d='.base64_encode($resultDocs->idEstudante);

					$msg = ("Super Estágios Informa:") . utf8_encode($resultDocs->motivo) . " ou no link abaixo $link";


					$mensagem = rawurlencode($msg);
                    
					$tipo_mensagem = 1;


					$array_valores = array(
						'auth' => "Wpp-GrupoSuper",
						'metodo' => "inserir_mensagem",
						'CodigoZap' => $resultado_franquia->cod_zap,
						'Mensagem' => $msg,
						'Destinatario' => $telefone,
						'tipo_mensagem' => $tipo_mensagem,
						'id_origem' => $resultDocs->id_informacoes,
					);
					$retorno = comunica_api($array_valores,"https://wpp.gruposuper.com.br/api/index.php");

					var_dump($retorno);

					$update = "UPDATE informacoes
                               SET Atendido = '71'
                               WHERE id_informacoes = '$resultDocs->id_informacoes'";						

					$update = $database->query($update);
				}
                else
                {
					$update = "UPDATE informacoes
                               SET Atendido = '72'
                               WHERE id_informacoes = '$resultDocs->id_informacoes'";						

					$update = $database->query($update);

				}//fim do Else Telefone
			}
            else
            {
				$q_infos_antigos = $database->query("SELECT inf.id_informacoes
                                                     FROM informacoes inf
                                                     WHERE inf.tipo_informacao = '6'
                                                     AND inf.Atendido = '60'						
                                                     AND inf.idEstudante = '$resultDocs->idEstudante'");

				while ($r_infos_antigos = $database->fetch_object($q_infos_antigos))
                {
					$database->query("UPDATE informacoes
                                      SET Atendido = 77
                                      WHERE id_informacoes = '$r_infos_antigos->id_informacoes'");
				}//Fim do While
			}//fim do Else retorno
		}// Fim do While

        echo "<p>Mensagens de DOCUMENTOS transferidas com sucesso.</p>";

	}
    else
    {
		echo "<p>Nenhuma mensagem de DOCUMENTOS para ser enviada.</p>";
	}
}

echo "<p id='mensagem-fim'>Fim da Transferência</p>";

?>