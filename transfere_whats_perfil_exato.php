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
/*********************************************** PERFIL EXATO ***********************************************/
/************************************************************************************************************/

// Enquanto houver resultados
while ($resultado_franquia = $database->fetch_object($consulta_franquia))
{
    echo "<h1>Transferindo Whats de " . utf8_encode($resultado_franquia->franquia) . "</h1>";

	echo "<p>Buscando Perfil Exato de " . utf8_encode($resultado_franquia->franquia) .  "</p>";

	$query_perfil = "SELECT pe.id,
                            e.tel1,
                            e.tel2,
                            e.tel3,
                            e.tel1_tipo,
                            e.tel2_tipo,
                            e.tel3_tipo,
                            (Substring_index(e.nome, ' ', 1)) as primeiro_nome,
                            pe.id_vaga, e.id AS idEstagiario,
                            ev.status AS status_vaga,
                            e.auth
	
                    FROM perfil_exato pe
                    JOIN estagiarios e ON e.id = pe.idEstagiario
                    JOIN empresas_vagas ev ON ev.id_vaga = pe.id_vaga
                    JOIN franquia_empresas fe ON ev.id_empresa = fe.id_empresa
                    WHERE pe.passo = 1
                    AND (e.tel1_tipo = 'Celular' || e.tel2_tipo = 'Celular' || e.tel3_tipo = 'Celular')
                    AND pe.sms = 1
                    AND pe.data_envio_SMS IS NULL
                    AND fe.id_franquia IN ($resultado_franquia->id)
                    AND IF(fe.polo > 1, fe.polo = ev.id_polo, 1 = 1)";

	$perfil_exato = $database->query($query_perfil);

	if ($database->num_rows($perfil_exato))
    {

		$text = $database->query("SELECT texto,
                                         id
                                  FROM modelos_whats
                                  WHERE tipo_mensagem = 1
                                  AND status = 1
                                  AND id_franquia = '$resultado_franquia->id'"
        );

		$modelo = false;

		if ($database->num_rows($text))
        {
			$modelo = true;
			$texto_modelo = $database->fetch_object($text)->texto;
			$texto_modelo = utf8_encode($texto_modelo);
		}


		while ($object_perfil_exato = $database->fetch_object($perfil_exato))
        {
			$q_situacao_vaga = $database->query("SELECT id_selecao
                                                 FROM selecao_vagas
                                                 WHERE idEstagiario = '$object_perfil_exato->idEstagiario'
                                                 AND id_vaga = '$object_perfil_exato->id_vaga'"
            );

			$n_situacao_vaga = $database->num_rows($q_situacao_vaga);

			if ($n_situacao_vaga || $object_perfil_exato->status_vaga != 2)
            {
	    		$database->query("UPDATE perfil_exato
                                  SET sms = 3
                                  WHERE id = '$object_perfil_exato->id'"
                ); // Perda de Objeto

	    		continue;
	    	}
            else
            {			

		    	$sql_ultimos_3_dias = $database->query("SELECT id FROM perfil_exato 
		    	                                        WHERE idEstagiario = '$object_perfil_exato->idEstagiario' 
		    		                                    AND data_envio_SMS >= DATE_FORMAT(DATE_ADD(NOW(),INTERVAL -3 day),'%Y-%m-%d')"
                );

		    	$tel = "";

		    	if(!strcmp($object_perfil_exato->tel1_tipo,"Celular")) $tel = $object_perfil_exato->tel1;

		    	elseif(!strcmp($object_perfil_exato->tel2_tipo,"Celular")) $tel = $object_perfil_exato->tel2;

		    	elseif(!strcmp($object_perfil_exato->tel3_tipo,"Celular")) $tel = $object_perfil_exato->tel3;

		    	$telefone = str_replace('-', '', str_replace(' ', '', str_replace('(', '', str_replace(')', '', $tel))));

		    	/*$msg = utf8_encode($object_perfil_exato->primeiro_nome) . (", encontramos uma vaga com o seu perfil perfeita para você, acesse www.superestagios.com.br e insira o cód. $object_perfil_exato->id_vaga");*/

		    	$link = 'https://www.superestagios.com.br/wpp.php?a='.base64_encode($object_perfil_exato->auth).'&u='.base64_encode($object_perfil_exato->idEstagiario).'&t=1&d='.base64_encode($object_perfil_exato->id_vaga);

			    // $msg = "Ei " . utf8_encode($object_perfil_exato->primeiro_nome) . ("! Achei uma vaga de estágio perfeita pra você! Caso tenha interesse acesse o link abaixo e confira!! Aproveite! $link .Se não conseguir acessar através do link, acesse com seu login e senha direto pelo portal da Super Estágios");

		    	if ($modelo)
                {
		    		$msg = str_replace("[nome]",utf8_encode($object_perfil_exato->primeiro_nome) , str_replace("[link]", "$link", str_replace("[numero_vaga]","$object_perfil_exato->id_vaga",$texto_modelo)));
		    	}
                else
                {
		    		$msg = "Ei " . utf8_encode($object_perfil_exato->primeiro_nome) . ("! Achei uma vaga de estágio perfeita pra você! Caso tenha interesse acesse o link abaixo e confira!! Aproveite! Se não conseguir acessar através do link, acesse com seu login e senha direto pelo portal da Super Estágios.$link");		
		    	}		


		    	if(!$database->num_rows($sql_ultimos_3_dias))
                {
                        // SE POSSUIR CELULAR
						if ($telefone != "")
                        { 

					    //SE O TELEFONE NAO TIVER O NONO DIGITO 

							if(strlen($telefone) == 10)
                            {
								$antes = substr($telefone, 0, 2);

								$depois = substr($telefone, 2);

								$telefone = $antes . "9" . $depois;
							}

							$mensagem = rawurlencode($msg);

							$q_info = $database->query("SELECT id_informacoes
                                                        FROM informacoes
                                                        WHERE idEstudante = '$object_perfil_exato->idEstagiario'
                                                        AND resposta = '10'
                                                        AND motivo = '".utf8_decode($msg)."'
                                                        AND LEFT(data,10) = DATE_FORMAT(NOW(),'%Y-%m-%d')"
                            );

							if(!$database->num_rows($q_info))
                            {
					    	    // $id_franquia_array = pega_franquia_estagiario($object_perfil_exato->idEstagiario);
								$id_franquia_est = $resultado_franquia->id;

								$s = "INSERT INTO informacoes
                                                  (id_usuario,
                                                  idEstudante,
                                                  resposta,
                                                  tipo_informacao,
                                                  motivo,
                                                  data,
                                                  Atendido,
                                                  id_franquia,
                                                  id_pedido)

								                  VALUES
                                                  ('1',
                                                  '$object_perfil_exato->idEstagiario',
                                                  '10',
                                                  '8',
                                                  '".utf8_decode($msg)."',
                                                  NOW(),
                                                  71,
                                                  '$id_franquia_est',
                                                  '$object_perfil_exato->id_vaga')";

								$s = $database->query($s);

								$id_info = $database->insert_id();

								$database->query("UPDATE perfil_exato
                                                  SET data_envio_SMS = CURDATE()
                                                  WHERE id = '$object_perfil_exato->id'"
                                );
							}
                            else
                            {
								$id_info = $database->fetch_object($q_info)->id_informacoes;

								$database->query("UPDATE perfil_exato
                                                  SET data_envio_SMS = CURDATE()
                                                  WHERE id = '$object_perfil_exato->id'"
                                );

								$update = $database->query("UPDATE informacoes
                                                            SET Atendido = 71
                                                            WHERE id_informacoes = '$id_info'"
                                );
							}

							$tipo_mensagem = 5;

							$array_valores = array(
								'auth' => "Wpp-GrupoSuper",
								'metodo' => "inserir_mensagem",
								'CodigoZap' => $resultado_franquia->cod_zap,
								'Mensagem' => $msg,
								'Destinatario' => $telefone,
								'tipo_mensagem' => $tipo_mensagem,
								'id_origem' => $id_info,
							);

							$retorno = comunica_api($array_valores,"https://wpp.gruposuper.com.br/api/index.php");

							//var_dump($retorno);

						}
                        else
                        { //fim if telefone
							$database->query("UPDATE perfil_exato
                                              SET sms = 3
                                              WHERE id = '$object_perfil_exato->id'"
                            );
						}
					}//fim ultimos 3 dias
				}//fim else situação vaga
			}
			echo "<p>Mensagens de PERFIL EXATO transferidas com sucesso.</p>";
		}
    // Se 
    else
    {
        echo "<p>Nenhuma mensagem de PERFIL EXATO para ser transferidas.</p>";
    }
}

echo "<p id='mensagem-fim'>Fim da Transferência</p>";

?>