**Nome do Aluno**
Kauan Christian Barbosa dos Santos

**Nome do Projeto**
G.I.S — Geografia Incremental Simples

**Descrição Do Projeto**
O projeto é uma aplicação web completa para gerenciamento de informações geográficas do mundo. O sistema permite cadastrar, consultar, editar e excluir continentes, países, cidades e governantes, mantendo os dados organizados em um banco MySQL chamado `bd_mundo`.

A aplicação foi desenvolvida com uma estrutura simples e funcional, separando a interface visual, os scripts PHP e os arquivos do banco de dados. O usuário pode navegar pelas páginas do sistema, visualizar listas de registros, realizar buscas e gerenciar as informações por meio de formulários.

**Instalação E Uso**
Para executar o projeto, é necessário ter um servidor local com suporte a PHP e MySQL, como XAMPP.

Primeiro, extraia o arquivo do projeto e coloque a pasta dentro do diretório do servidor local. No XAMPP, por exemplo, a pasta deve ficar em:

`C:\xampp\htdocs\mundo-crud-php-mysql`

Depois, abra o painel do XAMPP e inicie os serviços `Apache` e `MySQL`.

Em seguida, acesse o phpMyAdmin pelo navegador:

`http://localhost/phpmyadmin`

Crie/importe o banco de dados usando o arquivo:

`database/bd_mundo1.sql`

Depois, se quiser testar o sistema já com informações cadastradas, importe também:

`database/dados_exemplo.sql`

Com o banco criado e o servidor ligado, acesse o sistema pelo navegador:

`http://localhost/mundo-crud-php-mysql/`

O arquivo `index.php` não deve ser aberto diretamente com duplo clique, pois arquivos PHP precisam ser executados por um servidor.

**Autenticação**

Após importar `database/bd_mundo1.sql`, o banco já inclui as seguintes contas de demonstração. Todas entram com `primeiro_acesso` ativo e, após um login bem-sucedido, são direcionadas obrigatoriamente à tela de troca de senha. A nova senha é armazenada como hash no banco e o campo `primeiro_acesso` passa a ser `0`.

| Usuário | Senha inicial | Tipo |
| --- | --- | --- |
| `admin` | `SenhaInicial0` | Administrador |
| `ana.souza` | `SenhaInicial1` | Usuário |
| `bruno.lima` | `SenhaInicial2` | Usuário |
| `carla.mendes` | `SenhaInicial3` | Usuário |

A nova senha deve ter pelo menos oito caracteres, uma letra maiúscula, uma minúscula e um número.

Somente contas do tipo **Administrador** podem criar, editar ou excluir registros. Os demais usuários têm acesso apenas à consulta das informações.

Para adicionar essas contas a um banco que já havia sido criado anteriormente, importe também `database/usuarios_exemplo.sql`.

Para redefinir a senha de um administrador já existente para `SenhaInicial0`, execute `database/atualizar_senha_admin.sql`.

Por segurança, três senhas incorretas consecutivas bloqueiam a conta. Para liberar um usuário bloqueado, um administrador deve atualizar o registro em `usuarios`, zerando `tentativas_falhas` e definindo `bloqueado_em` como `NULL`. Os eventos de login, bloqueio, logout e troca de senha ficam registrados na tabela `logs`.

**Tecnologias Utilizadas**
O projeto utiliza `HTML5` para estruturar as páginas, `CSS3` para estilização e responsividade, `JavaScript` para validações, confirmações de exclusão e pesquisa dinâmica, `PHP` para a lógica do sistema e comunicação com o banco, e `MySQL` para armazenamento dos dados.

A conexão com o banco é feita usando `PDO`, o que torna as consultas mais seguras. O sistema também utiliza validações no servidor, proteção por token CSRF em formulários e consultas SQL preparadas para reduzir riscos de falhas e injeção de SQL.

**Descrição Detalhada**
A aplicação possui quatro módulos principais: continentes, países, cidades e governantes.

No módulo de continentes, é possível cadastrar informações como nome, população, área em km² e total de países. O total de países é atualizado automaticamente pelo banco de dados por meio de gatilhos.

No módulo de países, cada país é vinculado a um continente existente. São cadastradas informações como nome, população, área, idioma, clima, regime político e moeda.

No módulo de cidades, cada cidade é associada a um país. O cadastro inclui nome, população, área, clima e data de fundação.

No módulo de governantes, o sistema permite cadastrar governantes associados a um país ou a uma cidade. O cadastro inclui nome, partido político, data de nascimento, idade, data de início do mandato e data final do mandato.

O sistema também possui uma página inicial com estatísticas, como quantidade de registros, cidades mais populosas, total de cidades por continente e cidade mais populosa de cada país. Além disso, existe uma busca dinâmica que permite pesquisar países e cidades pelo nome.

As exclusões respeitam a integridade referencial do banco. Por exemplo, não é permitido excluir um país que ainda possui cidades vinculadas, nem excluir uma cidade ou país que possui governante associado. Isso evita registros órfãos e mantém o banco consistente.
