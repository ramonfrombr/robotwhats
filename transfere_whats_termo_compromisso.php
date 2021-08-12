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
/***************************************** TERMO DE COMPROMISSO *********************************************/
/************************************************************************************************************/

// Enquanto houver resultados
while ($resultado_franquia = $database->fetch_object($consulta_franquia))
{
	echo "<h1>Transferindo Whats de " . utf8_encode($resultado_franquia->franquia) . "</h1>";

	echo "<p>Buscando TC's de " . utf8_encode($resultado_franquia->franquia) . "</p>";

    $consulta_tc = $database->query("SELECT tc.id,
                                            UPPER(e.nome) AS nome,
                                            e.tel1,
                                            e.tel1_tipo,
                                            e.tel2,
                                            e.tel2_tipo,
                                            e.tel3,
                                            e.tel3_tipo,
                                            tc.empresa AS id_empresa,
                                            e.sexo,
                                            tc.data_ini,
                                            tc.idEstudante
                                    FROM estagiarios e 
                                    JOIN termo_compromisso tc ON e.id = tc.idEstudante
                                    JOIN franquia_empresas fe ON tc.empresa = fe.id_empresa
                                    WHERE tc.sms = 0
                                    AND tc.data_ini >= DATE_FORMAT(DATE_ADD(NOW(),INTERVAL -20 day),'%Y-%m-%d') 
                                    AND (tel1_tipo = 'Celular' || tel2_tipo = 'Celular' || tel3_tipo = 'Celular')
                                    AND fe.id_franquia IN ($resultado_franquia->id)
                                    AND IF(fe.polo > 1, fe.polo = tc.id_polo, 1 = 1)"
    );


	if ($database->num_rows($consulta_tc))
    {
		while ($resultado_tc = $database->fetch_object($consulta_tc))
        {

			$nome_completo = utf8_encode($resultado_tc->nome);

			$primeiro_nome = explode(" ",$nome_completo);

			$primeiro_nome = $primeiro_nome[0];

			$tel = "";

			if(!strcmp($resultado_tc->tel1_tipo,"Celular"))
            {
                $tel = $resultado_tc->tel1;
            } 
			elseif(!strcmp($resultado_tc->tel2_tipo,"Celular"))
            {
                $tel = $resultado_tc->tel2;
            }
			elseif(!strcmp($resultado_tc->tel3_tipo,"Celular"))
            {
                $tel = $resultado_tc->tel3;
            }

			$telefone = str_replace('-', '', str_replace(' ', '', str_replace('(', '', str_replace(')', '', $tel))));

			$empresa = "SELECT UPPER(nome_fantasia) AS nome
                        FROM empresas
                        WHERE id = '$resultado_tc->id_empresa'";

			$empresa = $database->query($empresa);

			$empresa = $database->fetch_object($empresa);

			$empresa = addslashes(utf8_encode($empresa->nome));

			$data_ini = explode("-", $resultado_tc->data_ini);

			$data_ini = $data_ini[2]."/".$data_ini[1];


			if(!strcmp($resultado_tc->sexo,"Feminino")) 
            {
				$msg = ("Parabéns $primeiro_nome, você foi contratada para estagiar na empresa $empresa. Acesse seu painel em WWW.SUPERESTAGIOS.COM.BR");
            }
			else
            {
				$msg = ("Parabéns $primeiro_nome, você foi contratado para estagiar na empresa $empresa. Acesse seu painel em WWW.SUPERESTAGIOS.COM.BR");
            }								  


            // SE POSSUIR CELULAR
			if($telefone != "")
            { 
			    //SE O TELEFONE NAO TIVER O NONO DIGITO 
				if(strlen($telefone)==10)
                {
					$antes = substr($telefone, 0, 2);

					$depois = substr($telefone, 2);

					$telefone = $antes . "9" . $depois;
				}

				$msg = addslashes($msg);

				$q_info = $database->query("SELECT id_informacoes
                                            FROM informacoes
                                            WHERE idEstudante = '$resultado_tc->idEstudante'
                                            AND resposta = '10'
                                            AND motivo = '".utf8_decode($msg)."'
                                            AND LEFT(data,10) = DATE_FORMAT(NOW(),'%Y-%m-%d')"
                );

				if(!$database->num_rows($q_info))
                {

					$id_franquia_est = $resultado_franquia->id;

					$s = "INSERT INTO informacoes
                          (id_usuario, idEstudante, resposta, tipo_informacao, motivo, data, Atendido, id_franquia)
					      VALUES
                          ('1', '$result->idEstudante', '10', '8', '".utf8_decode($msg)."', NOW(), 71, '$id_franquia_est')";

					$s = $database->query($s);

					$id_info = $database->insert_id();
				}
                else
                {
					$id_info = $database->fetch_object($q_info)->id_informacoes;
				}

				$update = "UPDATE termo_compromisso
                           SET sms = '1'
                           WHERE id = '$resultado_tc->id'";

				$update = $database->query($update);

				$database->query("UPDATE informacoes
                                  SET Atendido = 71
                                  WHERE id_informacoes = '$id_info'");

				$tipo_mensagem = 2;

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

				var_dump($retorno);

			}//Fim do if telefone
		}//Fim do while Rows
		echo "<p>Mensagens de TERMO DE COMPROMISSO transferidas com sucesso.</p>";
	}
    else
    {
		echo "<p>Nenhuma mensagem de TERMO DE COMPROMISSO para ser transferidas.</p>";
	}//Fim Else Num Rows TC
}

echo "<p id='mensagem-fim'>Fim da Transferência</p>";
?>