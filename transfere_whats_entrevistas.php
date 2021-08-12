<?

echo "1";

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
/************************************************* ENTREVISTAS **********************************************/
/************************************************************************************************************/

// Enquanto houver resultados
while ($resultado_franquia = $database->fetch_object($consulta_franquia))
{
	echo "<h1>Transferindo Whats de " . utf8_encode($resultado_franquia->franquia) . "</h1>";

	echo "<p>Buscando Entrevistas de " . utf8_encode($resultado_franquia->franquia) . "</p>";

	$dia_semana = date("w", strtotime(date("Y-m-d")));

    // SE FOR SEXTA-FEIRA
	if($dia_semana == 5)
    { 
		$busca_dia1 = "AND ee.dia = CURDATE() + INTERVAL 3 day";

		$busca_dia2 = "AND ep.data = CURDATE() + INTERVAL 3 day";

		$prox_dia = "Segunda-Feira";
	}
    // OUTROS DIAS DA SEMANA
    else
    { 
		$busca_dia1 = "AND ee.dia = CURDATE() + INTERVAL 1 day";

		$busca_dia2 = "AND ep.data = CURDATE() + INTERVAL 1 day";

		$prox_dia = "Amanhã";
	}

	$query_entrevista = "(SELECT ee.dia, ee.hora, ee.id_selecao AS id, (e.nome) AS nome, e.tel1, e.tel1_tipo, e.tel2, e.tel2_tipo, e.tel3, e.tel3_tipo, em.id_empresa, 1 AS tipo_mensagem, e.sexo, e.id AS idEstagiario,e.auth
	FROM estagiarios_entrevista ee 
	JOIN empresas_vagas em ON ee.id_vaga  = em.id_vaga 
	JOIN franquia_empresas fe ON em.id_empresa = fe.id_empresa
	JOIN estagiarios e ON ee.idEstagiario = e.id
	WHERE ee.participar = 0
	AND fe.id_franquia IN ($resultado_franquia->id)
	AND IF(fe.polo > 1, fe.polo = em.id_polo, 1 = 1)
	AND ee.sms = 0
	AND ee.whatsapp = 0
	AND (tel1_tipo = 'Celular' || tel2_tipo = 'Celular' || tel3_tipo = 'Celular')
	AND ee.dia > CURDATE() )

	UNION ALL

	(SELECT ee.dia, ee.hora, ee.id_selecao AS id, (e.nome) AS nome, e.tel1, e.tel1_tipo, e.tel2, e.tel2_tipo, e.tel3, e.tel3_tipo, em.id_empresa, 2 AS tipo_mensagem, e.sexo, e.id AS idEstagiario,e.auth
	FROM estagiarios_entrevista ee 
	JOIN empresas_vagas em ON ee.id_vaga  = em.id_vaga 
	JOIN franquia_empresas fe ON em.id_empresa = fe.id_empresa
	JOIN estagiarios e ON ee.idEstagiario = e.id
	WHERE ee.participar = 1
	AND fe.id_franquia IN ($resultado_franquia->id)
	AND IF(fe.polo > 1, fe.polo = em.id_polo, 1 = 1)
	AND ee.sms NOT IN (2)
	AND ee.whatsapp != 2
	AND (tel1_tipo = 'Celular' || tel2_tipo = 'Celular' || tel3_tipo = 'Celular')
	".$busca_dia1." )";

	$entrevista = $database->query($query_entrevista);

	if ($database->num_rows($entrevista))
    {
		while($resultEntrevista = $database->fetch_object($entrevista))
        {

			$nome_completo = utf8_encode($resultEntrevista->nome);

			$primeiro_nome = explode(" ",$nome_completo);

			$primeiro_nome = $primeiro_nome[0];

			$tel = "";

			if(!strcmp($resultEntrevista->tel1_tipo,"Celular")) $tel = $resultEntrevista->tel1;

			elseif(!strcmp($resultEntrevista->tel2_tipo,"Celular")) $tel = $resultEntrevista->tel2;

			elseif(!strcmp($resultEntrevista->tel3_tipo,"Celular")) $tel = $resultEntrevista->tel3;

			$telefone = str_replace('-', '', str_replace(' ', '', str_replace('(', '', str_replace(')', '', $tel))));

			$empresa = "SELECT UPPER(nome_fantasia) AS nome
                        FROM empresas
                        WHERE id = '$resultEntrevista->id_empresa'";

			$empresa = $database->query($empresa);

			$empresa = $database->fetch_object($empresa);

			$empresa = str_replace("'",'',utf8_encode($empresa->nome)); 

			$dia = explode("-",$resultEntrevista->dia);

			$dia = $dia[2]."/".$dia[1];

			$hora = explode(":",$resultEntrevista->hora);

			$hora = $hora[0].":".$hora[1];

			$data = $dia." as ".$hora;

			if($resultEntrevista->tipo_mensagem == 1)
            {

				$link = 'https://www.superestagios.com.br/wpp.php?a='.base64_encode($resultEntrevista->auth).'&u='.base64_encode($resultEntrevista->idEstagiario).'&t=2&d='.base64_encode($resultEntrevista->id);

				if(!strcmp($resultEntrevista->sexo,"Feminino"))
                {
                    $msg = ("$primeiro_nome, você foi selecionada para uma entrevista de estágio na $empresa, no dia $data. Confirme sua participação no link $link");
                }
				else
                {
                    $msg = ("$primeiro_nome, você foi selecionado para uma entrevista de estágio na $empresa, no dia $data. Confirme sua participação no link $link");
                }

				/*if(!strcmp($resultEntrevista->sexo,"Feminino")) $msg = ("$primeiro_nome, você foi selecionada para uma entrevista de estágio na $empresa, no dia $data.");

				else $msg = ("$primeiro_nome, você foi selecionado para uma entrevista de estágio na $empresa, no dia $data.");*/

			}
            elseif
            ($resultEntrevista->tipo_mensagem == 2)
            {

				$link = 'https://www.superestagios.com.br/wpp.php?a='.base64_encode($resultEntrevista->auth).'&u='.base64_encode($resultEntrevista->idEstagiario).'&t=3&d='.base64_encode($resultEntrevista->id);

				$msg =  ("Lembrete da Super Estágios: $primeiro_nome, $prox_dia você terá uma entrevista de estágio na $empresa as $hora. Confira os detalhes no link abaixo e Boa Sorte! $link");

				/*$msg =  ("Lembrete da Super Estágios: $primeiro_nome, $prox_dia você terá uma entrevista de estágio na $empresa as $hora. Boa Sorte!");*/

			}
            elseif ($resultEntrevista->tipo_mensagem == 3)
            {
				if(!strcmp($resultEntrevista->sexo,"Feminino")) $msg = ("$primeiro_nome, você foi selecionada para uma prova de processo seletivo na Super Estágios, no dia $data.");

				else $msg = ("$primeiro_nome, você foi selecionado para uma prova de processo seletivo na Super Estágios, no dia $data.");				
			}
            elseif($resultEntrevista->tipo_mensagem == 4)
            {
				$msg = ("Lembrete da Super Estágios: $primeiro_nome, $prox_dia você terá uma prova de processo seletivo na Super Estágios as $hora. Boa Sorte!");				
			}

            // SE POSSUIR CELULAR
			if($telefone != "")
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
                                            WHERE idEstudante = '$resultEntrevista->idEstagiario'
                                            AND resposta = '10'
                                            AND motivo = '".utf8_decode($msg)."'
                                            AND LEFT(data,10) = DATE_FORMAT(NOW(),'%Y-%m-%d')");

				if(!$database->num_rows($q_info))
                {

			    	// $id_franquia_array = pega_franquia_estagiario($resultEntrevista->idEstagiario);
					$id_franquia_est = $resultado_franquia->id;

					$s = "INSERT INTO informacoes
                        
                          (id_usuario, idEstudante, resposta, tipo_informacao, motivo, data, Atendido, id_franquia, id_pedido)

					      VALUES
                          
                          ('1', '$resultEntrevista->idEstagiario', '10', '8', '".utf8_decode($msg)."', NOW(),71, '$id_franquia_est', '$resultEntrevista->id')";

					$s = $database->query($s);

					$id_info = $database->insert_id();

				}
                else
                {
					$id_info = $database->fetch_object($q_info)->id_informacoes;

					$update = "UPDATE informacoes
                               SET Atendido = '71'
                               WHERE id_informacoes = '$id_info'";						

					$update = $database->query($update);
				}

				if($resultEntrevista->tipo_mensagem == 1)
                {		
					$update = "UPDATE estagiarios_entrevista
                               SET sms = '1', whatsapp = '1'
                               WHERE id_selecao = '$resultEntrevista->id'";	
				}
                elseif($resultEntrevista->tipo_mensagem == 2)
                {		
					$update = "UPDATE estagiarios_entrevista
                               SET sms = '2', whatsapp = '1'
                               WHERE id_selecao = '$resultEntrevista->id'";		
				}

				$update = $database->query($update);

				$tipo_mensagem = 4;

				$array_valores = array(
					'auth' => "Wpp-GrupoSuper",
					'metodo' => "inserir_mensagem",
					'CodigoZap' => $resultado_franquia->cod_zap,
					'Mensagem' => $msg,
					'Destinatario' => $telefone,
					'tipo_mensagem' => $tipo_mensagem,
					'id_origem' => $resultEntrevista->id,
				);

				$retorno = comunica_api($array_valores,"https://wpp.gruposuper.com.br/api/index.php");

				var_dump($retorno);

			}
		}//Fim do While
		echo "<p>Mensagens de ENTREVISTAS enviadas com sucesso</p>";
	}
    else
    {
		echo "<p>Nenhuma mensagem de ENTREVISTAS para ser enviada.</p>";
	}
}

echo "<p id='mensagem-fim'>Fim da Transferência</p>";

?>