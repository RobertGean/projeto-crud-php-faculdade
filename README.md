# PDV MVC — Sistema de Ponto de Venda em PHP com MVC e Design Patterns

> Projeto acadêmico: refatoração do PDV procedural visto em aula para a **arquitetura MVC**, com **Design Patterns** aplicados a problemas reais do sistema e três novos CRUDs: **Cadastro de Clientes**, **Carrinho de Vendas** e **Relatórios de Vendas**.

| Critério da atividade | Onde está |
|---|---|
| **MVC** | Pastas `app/Models`, `app/Views` e `app/Controllers` + Front Controller em `public/index.php` — ver [Arquitetura MVC](#2-arquitetura-mvc) |
| **Design Patterns** | `app/Core/Database.php`, `app/Patterns/*`, `app/Repositories/*`, `app/Services/VendaService.php` — ver [Design Patterns](#3-design-patterns-adotados) |
| **CRUDs** | `ClienteController`, `CarrinhoController`, `RelatorioController` (+ `ProdutoController` refatorado) — ver [Como usar](#7-como-usar) |
| **POP de implantação** | Seção [POP – Procedimento Operacional Padrão](#6-pop--procedimento-operacional-padrão-de-implantação) |

---

## Sumário

1. [Sobre o projeto](#1-sobre-o-projeto)
2. [Arquitetura MVC](#2-arquitetura-mvc)
3. [Design Patterns adotados](#3-design-patterns-adotados)
4. [Antes x Depois](#4-antes-x-depois)
5. [Modelo de dados](#5-modelo-de-dados)
6. [POP – Procedimento Operacional Padrão de Implantação](#6-pop--procedimento-operacional-padrão-de-implantação)
7. [Como usar](#7-como-usar)
8. [Autor e disciplina](#8-autor-e-disciplina)

---

## 1. Sobre o projeto

### Objetivo

Evoluir o sistema de PDV fornecido em aula (scripts PHP soltos que misturavam HTML, SQL e regra de negócio) para uma aplicação organizada em camadas, segura e fácil de manter, sem usar framework — para que cada peça da arquitetura fique visível no código.

### Funcionalidades

- **Dashboard**: vendas do dia, faturamento do mês, carrinhos abertos, produtos com estoque baixo (≤ 5) e com validade vencida/vencendo em 30 dias.
- **Produtos** (refatorado): listagem com busca por nome **ou código** e paginação; cadastro e edição com upload de imagem validado (JPG/PNG/WEBP, até 2 MB, nome único); exclusão bloqueada quando há vendas, com opção de **inativar**.
- **1 – Clientes** (CRUD completo): listagem com busca por nome/CPF e paginação, cadastro, visualização com **histórico de compras**, edição, exclusão (ou inativação, se o cliente tiver vendas). Validação de CPF pelos dígitos verificadores, e-mail e campos obrigatórios, mantendo os valores digitados quando há erro.
- **PDV**: busca rápida de produtos que joga o item direto no carrinho atual (substitui o antigo botão "Vender").
- **2 – Carrinho de vendas** (CRUD completo): criar (com ou sem cliente), adicionar produtos pela busca ou pelo código, alterar quantidade, remover item, esvaziar, cancelar, excluir, listar carrinhos abertos para retomar e **finalizar** escolhendo a forma de pagamento (com cálculo de desconto, acréscimo e troco). Estoque validado em cada alteração e de novo, com bloqueio de linha, na finalização.
- **3 – Relatórios de vendas** (CRUD sobre as vendas): filtros por período, cliente, forma de pagamento e status; indicadores (quantidade, faturamento, ticket médio); gráficos (Chart.js) de vendas por forma de pagamento e produtos mais vendidos; relatórios por período/cliente/produto; cupom da venda com botão imprimir; edição de forma de pagamento/observação; cancelamento com estorno de estoque; exclusão definitiva só de vendas canceladas; **exportação CSV** (`;`, UTF-8 com BOM para o Excel).

### Tecnologias

| Tecnologia | Uso |
|---|---|
| PHP 8.1+ (sem framework) | Linguagem do back-end — tipagem estrita, propriedades `readonly`, `match`, tipo de retorno `never` |
| PDO + `pdo_pgsql` | Acesso ao banco com prepared statements |
| PostgreSQL no [Neon](https://neon.tech) | Banco de dados em nuvem (serverless) |
| Bootstrap 5.3 + Bootstrap Icons (CDN) | Interface responsiva |
| Chart.js 4 (CDN) | Gráficos dos relatórios |
| Apache (`.htaccess`) ou `php -S` | Servidor web |
| Composer (opcional) | Apenas autoload PSR-4 — há autoload próprio como fallback |

---

## 2. Arquitetura MVC

**MVC (Model–View–Controller)** é a *arquitetura* do sistema: ela divide a aplicação em camadas com responsabilidades separadas.

| Camada | Pasta | Responsabilidade | Regra no projeto |
|---|---|---|---|
| **Model** | `app/Models` + `app/Repositories` | Entidades do domínio (`Produto`, `Cliente`, `Carrinho`, `ItemCarrinho`, `Venda`, `ItemVenda`) e acesso a dados | **Único lugar com SQL**, sempre com prepared statements |
| **View** | `app/Views` | Apresentação (HTML + Bootstrap) | Sem SQL e sem lógica de negócio; tudo é escapado com `e()` |
| **Controller** | `app/Controllers` | Recebe a requisição, valida a entrada, chama Services/Repositories e escolhe a View | Sem HTML e sem SQL |
| Service Layer | `app/Services` | Regras de negócio que envolvem várias entidades (venda, carrinho, upload, relatórios) | Controla transações |
| Core | `app/Core` | Infraestrutura: Router, Request, View, Database, CSRF, Flash, Logger, tratamento de erros | — |

### Fluxo de uma requisição

```mermaid
flowchart LR
    N[Navegador] -->|"GET /index.php?rota=clientes/editar&id=5<br/>ou /clientes/editar/5"| FC["Front Controller<br/>public/index.php"]
    FC --> R["Router<br/>config/rotas.php"]
    R -->|"POST? confere token CSRF"| C["Controller<br/>ClienteController::editar()"]
    C --> S["Service<br/>(regras de negócio)"]
    C --> RP["Repository<br/>ClienteRepository"]
    S --> RP
    RP -->|"PDO prepared statement"| DB[("PostgreSQL<br/>Neon")]
    RP -->|"entidade Cliente"| C
    C -->|"dados sempre definidos"| V["View<br/>clientes/form.php<br/>+ layouts/main.php"]
    V -->|HTML| N
```

### Árvore de pastas comentada

```text
pdv-mvc/
├── public/                     # ÚNICA pasta exposta na web (DocumentRoot)
│   ├── index.php               # Front Controller: toda requisição entra aqui
│   ├── .htaccess               # rewrite de URLs amigáveis para o index.php
│   ├── assets/css/app.css      # estilos próprios (o resto é Bootstrap)
│   ├── assets/js/              # app.js (confirmações, prévia do pagamento) e relatorios.js (gráficos)
│   └── uploads/produtos/       # imagens enviadas (scripts bloqueados por .htaccess)
├── app/
│   ├── bootstrap.php           # autoload (Composer ou próprio), config, timezone, erros
│   ├── Core/                   # Router, Request, Controller base, View, Database (Singleton),
│   │                           # Csrf, Flash, Session, Logger, ErrorHandler, Validador, Paginacao
│   ├── Controllers/            # Home, Produto, Cliente, Pdv, Carrinho, Relatorio
│   ├── Models/                 # Produto, Cliente, Carrinho, ItemCarrinho, Venda, ItemVenda
│   ├── Repositories/           # Contracts/ (interfaces) + implementações PostgreSQL
│   ├── Services/               # VendaService, CarrinhoService, RelatorioService, UploadService
│   ├── Patterns/
│   │   ├── Strategy/           # formas de pagamento
│   │   ├── Observer/           # observadores da venda
│   │   └── Factory/            # fábrica de relatórios
│   └── Views/                  # layouts/, partials/, home/, produtos/, clientes/, pdv/, carrinho/, relatorios/, erros/
├── config/
│   ├── config.php              # lê o .env (mesma ideia do db.php legado)
│   └── rotas.php               # tabela de rotas (GET/POST)
├── database/
│   ├── schema.sql              # CREATE TABLE idempotente (a tabela produtos já existe no Neon)
│   ├── seed.sql                # 10 produtos, 5 clientes, 8 vendas de exemplo
│   └── migrate.php             # executa schema.sql (+ seed.sql com --seed) pela linha de comando
├── docs/prints/                # capturas de tela para o README
├── legado/                     # código original (somente para comparação; bloqueado na web)
├── storage/logs/               # app.log (erros) e vendas.log (Observer)
├── .env.example                # modelo de configuração (o .env real NÃO vai para o Git)
├── .htaccess                   # usado só quando não dá para apontar o DocumentRoot para public/
└── composer.json               # apenas autoload PSR-4 "App\\": "app/"
```

### Exemplo real: fluxo de "Finalizar venda"

```mermaid
sequenceDiagram
    actor Op as Operador
    participant FC as public/index.php
    participant R as Router
    participant C as CarrinhoController
    participant VS as VendaService
    participant St as FormaPagamentoStrategy
    participant Repo as Repositories
    participant Obs as Observers
    participant DB as PostgreSQL

    Op->>FC: POST rota=carrinho/finalizar (id, forma, valor recebido, _csrf)
    FC->>R: despachar(Request)
    R->>R: valida token CSRF
    R->>C: finalizar()
    C->>VS: finalizar(id, forma, recebido, obs)
    VS->>St: FormaPagamentoFactory::criar(forma)
    VS->>DB: BEGIN
    VS->>Repo: bloquear carrinho (SELECT ... FOR UPDATE)
    VS->>Repo: estoqueComBloqueio(produto) (FOR UPDATE) p/ cada item
    VS->>St: calcular(subtotal, recebido) → desconto/acréscimo/troco
    VS->>Repo: inserir venda + venda_itens
    VS->>Obs: notificar("venda.finalizada")
    Obs->>Repo: BaixaEstoqueObserver → baixarEstoque()
    Obs->>Obs: LogVendaObserver → storage/logs/vendas.log
    VS->>Repo: carrinho = finalizado
    VS->>DB: COMMIT (ou ROLLBACK se algo falhar)
    VS-->>C: Venda
    C-->>Op: redirect + flash "Venda #N finalizada" → cupom
```

Trecho do controller (sem SQL, sem HTML — só orquestra):

```php
// app/Controllers/CarrinhoController.php
public function finalizar(): void
{
    $id = $this->id();
    try {
        $venda = VendaService::padrao()->finalizar(
            $id,
            $this->request->texto('forma_pagamento'),
            $this->request->decimal('valor_recebido'),
            $this->request->texto('observacao')
        );
    } catch (RegraNegocioException $e) {
        Flash::erro($e->getMessage());
        $this->redirecionar('carrinho/ver', ['id' => $id]);
    }
    Flash::sucesso("Venda #{$venda->id} finalizada! ...");
    $this->redirecionar('relatorios/venda', ['id' => $venda->id]);
}
```

### Rotas

O `Router` aceita os dois formatos, porque a hospedagem pode ser um cPanel compartilhado sem `mod_rewrite`:

| Formato | Exemplo | Quando usar |
|---|---|---|
| Query string (padrão) | `index.php?rota=clientes/editar&id=5` | Funciona em qualquer servidor |
| URL amigável | `/clientes/editar/5` | Com `.htaccess` (Apache) ou `php -S`; ative com `APP_URL_AMIGAVEL=true` |

Ações que alteram dados (salvar, excluir, cancelar, finalizar) são **somente POST** e passam pela verificação de **token CSRF** no próprio Router.

---

## 3. Design Patterns adotados

> **Padrões de projeto não são arquitetura de software.** O **MVC é a arquitetura** — a forma como o sistema inteiro está dividido. Os **design patterns** são **soluções pontuais** para problemas específicos que aparecem *dentro* dessa arquitetura (criar a conexão, escolher um cálculo, reagir a um evento). Por isso cada padrão abaixo vem acompanhado do problema concreto do PDV que ele resolve — evitando o *overengineering* discutido em aula.
>
> **Por que não Laravel?** Por fins didáticos: sem framework, cada peça (Front Controller, Router, Controllers, Views, Repositories) fica explícita no código. A estrutura segue o mesmo modelo MVC dos frameworks (Laravel, Symfony, CodeIgniter): `public/index.php` como entrada, arquivo de rotas, controllers finos e views com layout — quem entende este projeto reconhece a organização de um projeto Laravel.

| Padrão | Classificação | Onde | Problema resolvido |
|---|---|---|---|
| Singleton | Criacional | `app/Core/Database.php` | Uma única conexão PDO com o Neon por requisição |
| Factory Method | Criacional | `app/Patterns/Factory/RelatorioFactory.php` | Escolher o relatório certo sem `switch` espalhado |
| Repository | Estrutural / acesso a dados | `app/Repositories/*` + `Contracts/*` | Isolar todo o SQL atrás de interfaces |
| Front Controller | Estrutural (arquitetural) | `public/index.php` + `app/Core/Router.php` | Ponto único de entrada, CSRF e erros centralizados |
| Strategy | Comportamental | `app/Patterns/Strategy/*` | Regras diferentes por forma de pagamento |
| Observer | Comportamental | `app/Patterns/Observer/*` + `VendaService` | Reações à venda (estoque, log) sem acoplamento |
| Service Layer | Arquitetural (camada) | `app/Services/VendaService.php` | Venda atômica em transação, sem estoque negativo |

### 3.1 Singleton — `Database::getInstance()`

- **Definição**: garante que uma classe tenha **uma única instância** e oferece um ponto global de acesso a ela.
- **Classificação**: Criacional.
- **Problema no PDV**: no legado cada script fazia `include 'db.php'` e abria sua própria conexão. No Neon cada conexão custa um handshake TLS e conta no limite do plano. Além disso, a transação da venda precisa valer para **todos** os repositórios — eles precisam compartilhar a mesma conexão.
- **Arquivo**: `app/Core/Database.php` (usado por `app/Repositories/Repository.php`).

```php
final class Database
{
    private static ?PDO $instancia = null;
    private function __construct() {}          // ninguém faz "new Database()"
    private function __clone() {}

    public static function getInstance(): PDO
    {
        if (self::$instancia === null) {
            [$dsn, $usuario, $senha] = self::montarDsn(Config::get('db_url'));
            self::$instancia = new PDO($dsn, $usuario, $senha, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, ...]);
        }
        return self::$instancia;
    }
}
```

### 3.2 Factory Method — `RelatorioFactory::criar($tipo)`

- **Definição**: delega a um método fábrica a decisão de **qual classe concreta instanciar**, devolvendo-a pela interface comum.
- **Classificação**: Criacional. *(Implementado na forma de método fábrica estático/parametrizado — a variação mais usada em PHP.)*
- **Problema no PDV**: a tela de relatórios e a exportação CSV geram relatórios diferentes (por período, por cliente, por produto) conforme o parâmetro `tipo`. Sem a fábrica, o mesmo `switch` apareceria no controller e no CSV.
- **Arquivos**: `app/Patterns/Factory/Relatorio.php` (interface), `RelatorioPorPeriodo.php`, `RelatorioPorCliente.php`, `RelatorioPorProduto.php`, `RelatorioFactory.php`.

```php
final class RelatorioFactory
{
    private const TIPOS = [
        'periodo' => RelatorioPorPeriodo::class,
        'cliente' => RelatorioPorCliente::class,
        'produto' => RelatorioPorProduto::class,
    ];

    public static function criar(string $tipo, ?VendaRepositoryInterface $vendas = null): Relatorio
    {
        $classe = self::TIPOS[$tipo] ?? throw new RegraNegocioException("Tipo de relatório inválido");
        return new $classe($vendas ?? new VendaRepository());
    }
}

// Uso — a tela e o CSV tratam qualquer relatório da mesma forma:
$relatorio = RelatorioFactory::criar($filtros['tipo']);
foreach ($relatorio->linhas($filtros) as $linha) { ... }
```

### 3.3 Repository — `ProdutoRepository`, `ClienteRepository`, `CarrinhoRepository`, `VendaRepository`

- **Definição**: media o acesso aos dados como se fosse uma **coleção de objetos do domínio**, escondendo o SQL.
- **Classificação**: Estrutural / padrão de acesso a dados (catálogo de Martin Fowler, *Patterns of Enterprise Application Architecture*).
- **Problema no PDV**: no legado o SQL estava espalhado em todas as páginas (inclusive com concatenação de variáveis — SQL injection) e a função `buscarProduto()` usava a coluna errada (`codigo` em vez de `cod`). Agora **todo SQL fica em um só lugar**, sempre com prepared statements, e os controllers dependem de **interfaces** (`app/Repositories/Contracts`), não do PostgreSQL.
- **Arquivos**: `app/Repositories/Contracts/*Interface.php` e `app/Repositories/*Repository.php`.

```php
interface ProdutoRepositoryInterface
{
    public function buscar(string $termo, int $limite, int $offset, bool $somenteAtivos = false): array;
    public function baixarEstoque(int $id, int $quantidade): void;
    // ...
}

final class ProdutoRepository extends Repository implements ProdutoRepositoryInterface
{
    public function baixarEstoque(int $id, int $quantidade): void
    {
        $stmt = $this->db()->prepare(
            'UPDATE produtos SET quantidade = quantidade - :qtd WHERE id = :id AND quantidade >= :minimo'
        );
        $stmt->execute([':qtd' => $quantidade, ':minimo' => $quantidade, ':id' => $id]);
        if ($stmt->rowCount() === 0) {
            throw new RegraNegocioException("Estoque insuficiente para o produto #{$id}.");
        }
    }
}
```

### 3.4 Front Controller — `public/index.php` + `Router`

- **Definição**: um **único ponto de entrada** recebe todas as requisições e as encaminha ao handler adequado.
- **Classificação**: Estrutural (padrão arquitetural de aplicações web).
- **Problema no PDV**: no legado cada `.php` era acessado diretamente, e cada um precisava repetir conexão, tratamento de erros e (não tinha) proteção CSRF. Agora sessão, CSRF, autoload, tratamento de erros e roteamento ficam em um só lugar, e apenas `public/` fica exposta na web.
- **Arquivos**: `public/index.php`, `app/Core/Router.php`, `config/rotas.php`.

```php
// public/index.php
require dirname(__DIR__) . '/app/bootstrap.php';
Session::iniciar();
$router = new Router();
require BASE_PATH . '/config/rotas.php';     // $router->get('clientes', [ClienteController::class, 'index']); ...
$router->despachar(Request::capturar());     // valida CSRF em POST e chama o controller
```

### 3.5 Strategy — `FormaPagamentoStrategy`

- **Definição**: define uma família de algoritmos, encapsula cada um e os torna **intercambiáveis**.
- **Classificação**: Comportamental.
- **Problema no PDV**: cada forma de pagamento tem regra própria — **Dinheiro** 5% de desconto e troco, **PIX** 2% de desconto, **Débito** sem ajuste, **Crédito** 3% de acréscimo. Com `if/else` no serviço de venda, cada forma nova exigiria alterar a regra de venda (viola o princípio Aberto/Fechado). Com Strategy, basta criar uma classe.
- **Arquivos**: `app/Patterns/Strategy/FormaPagamentoStrategy.php`, `PagamentoBase.php`, `PagamentoDinheiro.php`, `PagamentoPix.php`, `PagamentoCartaoDebito.php`, `PagamentoCartaoCredito.php`, `FormaPagamentoFactory.php`, `ResultadoPagamento.php`.

```php
interface FormaPagamentoStrategy
{
    public function codigo(): string;
    public function percentualAjuste(): float;   // negativo = desconto, positivo = acréscimo
    public function aceitaTroco(): bool;
    public function calcular(float $subtotal, ?float $valorRecebido = null): ResultadoPagamento;
}

final class PagamentoDinheiro extends PagamentoBase
{
    public function percentualAjuste(): float { return -5.0; }
    public function aceitaTroco(): bool { return true; }
    // fecharPagamento() exige valor recebido >= total e calcula o troco
}

// No VendaService — não sabe qual estratégia concreta está usando:
$pagamento = FormaPagamentoFactory::criar($forma)->calcular($carrinho->total(), $valorRecebido);
```

```mermaid
classDiagram
    class FormaPagamentoStrategy {
        <<interface>>
        +codigo() string
        +nome() string
        +percentualAjuste() float
        +aceitaTroco() bool
        +calcular(subtotal, valorRecebido) ResultadoPagamento
    }
    class PagamentoBase {
        <<abstract>>
        +calcular(subtotal, valorRecebido) ResultadoPagamento
        #fecharPagamento(...) ResultadoPagamento
    }
    class PagamentoDinheiro {
        +percentualAjuste() -5.0
        +aceitaTroco() true
    }
    class PagamentoPix {
        +percentualAjuste() -2.0
    }
    class PagamentoCartaoDebito {
        +percentualAjuste() 0.0
    }
    class PagamentoCartaoCredito {
        +percentualAjuste() 3.0
    }
    class ResultadoPagamento {
        +float subtotal
        +float desconto
        +float acrescimo
        +float total
        +float valorRecebido
        +float troco
    }
    class FormaPagamentoFactory {
        +criar(codigo)$ FormaPagamentoStrategy
        +todas()$ array
    }
    class VendaService {
        +finalizar(carrinhoId, forma, recebido, obs) Venda
        +atualizar(vendaId, forma, recebido, obs) Venda
    }
    FormaPagamentoStrategy <|.. PagamentoBase
    PagamentoBase <|-- PagamentoDinheiro
    PagamentoBase <|-- PagamentoPix
    PagamentoBase <|-- PagamentoCartaoDebito
    PagamentoBase <|-- PagamentoCartaoCredito
    FormaPagamentoStrategy ..> ResultadoPagamento : cria
    FormaPagamentoFactory ..> FormaPagamentoStrategy : instancia
    VendaService --> FormaPagamentoStrategy : usa
```

### 3.6 Observer — `VendaService` notifica `BaixaEstoqueObserver` e `LogVendaObserver`

- **Definição**: quando um objeto (*Subject*) muda de estado, todos os seus dependentes (*Observers*) são **notificados automaticamente**.
- **Classificação**: Comportamental.
- **Problema no PDV**: finalizar ou cancelar uma venda dispara efeitos colaterais — baixar/estornar estoque, registrar log de auditoria (e, no futuro, enviar e-mail, emitir nota...). Colocar tudo no `VendaService` o acoplaria a cada tarefa. Com Observer, o serviço apenas anuncia o evento.
- **Arquivos**: `app/Patterns/Observer/VendaSubject.php`, `VendaObserver.php`, `BaixaEstoqueObserver.php`, `LogVendaObserver.php`; Subject concreto em `app/Services/VendaService.php`.
- **Detalhe importante**: o `BaixaEstoqueObserver` roda **dentro da transação** aberta pelo `VendaService` (mesma conexão do Singleton). Se a baixa de qualquer item falhar, a venda inteira sofre *rollback*.

```php
// Montagem (VendaService::padrao())
$servico->anexar(new BaixaEstoqueObserver($produtos));
$servico->anexar(new LogVendaObserver());

// Dentro de finalizar(), após gravar a venda:
$this->notificar(VendaObserver::FINALIZADA, $venda);

// BaixaEstoqueObserver
public function atualizar(string $evento, Venda $venda): void
{
    foreach ($venda->itens as $item) {
        match ($evento) {
            self::FINALIZADA => $this->produtos->baixarEstoque($item->produtoId, $item->quantidade),
            self::CANCELADA  => $this->produtos->estornarEstoque($item->produtoId, $item->quantidade),
            default          => null,
        };
    }
}
```

```mermaid
classDiagram
    class VendaSubject {
        <<interface>>
        +anexar(VendaObserver)
        +desanexar(VendaObserver)
        +notificar(evento, Venda)
    }
    class VendaObserver {
        <<interface>>
        +FINALIZADA = "venda.finalizada"
        +CANCELADA = "venda.cancelada"
        +atualizar(evento, Venda)
    }
    class VendaService {
        -observers VendaObserver[]
        +finalizar() Venda
        +cancelar() Venda
    }
    class BaixaEstoqueObserver {
        -produtos ProdutoRepositoryInterface
        +atualizar(evento, Venda)
    }
    class LogVendaObserver {
        +atualizar(evento, Venda)
    }
    VendaSubject <|.. VendaService
    VendaObserver <|.. BaixaEstoqueObserver
    VendaObserver <|.. LogVendaObserver
    VendaService o--> "0..*" VendaObserver : notifica
    BaixaEstoqueObserver --> ProdutoRepositoryInterface
```

### 3.7 Service Layer — `VendaService` (transação + `SELECT ... FOR UPDATE`)

- **Definição**: camada que define as **operações da aplicação** (casos de uso) e coordena entidades, repositórios e transações.
- **Problema no PDV**: o "Vender" do legado fazia só um `UPDATE` de quantidade — permitia **estoque negativo** e **não registrava a venda**. Finalizar uma venda agora envolve 5 tabelas, e precisa ser tudo-ou-nada.
- **Como**: `beginTransaction()` → trava o carrinho e cada produto com `SELECT ... FOR UPDATE` (duas vendas simultâneas do mesmo produto não passam ambas) → calcula com a Strategy → grava `vendas` e `venda_itens` → notifica observers → `commit()`; qualquer exceção → `rollBack()`. O `CHECK (quantidade >= 0)` no banco é a última barreira.

```php
private function emTransacao(callable $operacao): mixed
{
    $pdo = $this->conexao ??= Database::getInstance();
    $pdo->beginTransaction();
    try {
        $resultado = $operacao();
        $pdo->commit();
        return $resultado;
    } catch (Throwable $erro) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $erro;
    }
}
```

---

## 4. Antes x Depois

| Aspecto | Sistema legado (`legado/`) | Nova versão |
|---|---|---|
| Estrutura | Scripts soltos misturando HTML, SQL e regra | MVC + Service Layer + Repositories; um Front Controller |
| Entrada | Cada `.php` acessível diretamente | Só `public/` exposta; `app/`, `config/`, `.env`, `legado/` bloqueados |
| Banco | MySQL com credenciais no código → PDO/PostgreSQL | PDO/PostgreSQL (Neon), credenciais só no `.env` (fora do Git) |
| SQL | `INSERT` com concatenação de `$_POST` (SQL injection) | 100% prepared statements, só nos Repositories |
| `editar_produto.php` sem `?id` | Dezenas de *Undefined variable $produto* / *array offset on null* no `error_log` | Controller busca o registro e, se não existir, mostra 404 amigável; views sempre recebem variáveis definidas; warnings viram exceções e vão para o log |
| Busca no PDV | `buscarProduto('codigo', ...)` — coluna inexistente (a real é `cod`) | Busca por **nome ou código** (`nome ILIKE ... OR cod ILIKE ...`) |
| Venda | Só baixava a quantidade; aceitava estoque negativo; nada era registrado | Carrinho → venda com itens, pagamento, troco; transação + `FOR UPDATE` + `CHECK >= 0` |
| Upload | Sem validação; nome original (sobrescreve, aceita `.php`); falha do `move_uploaded_file` ignorada | Tipo real via `finfo` + `getimagesize`, até 2 MB, nome aleatório, erro tratado; scripts bloqueados na pasta |
| Erros | Mensagens do PDO/MySQL exibidas ao usuário | Log em `storage/logs/app.log`; usuário vê mensagem amigável (`APP_DEBUG=true` mostra detalhes só em dev) |
| CSRF / exclusão | Sem token; exclusão por GET sem confirmação | Token CSRF em todo POST (validado no Router); exclusões só por POST com confirmação |
| Mensagens | `echo "Produto atualizado!"` antes do `<html>` | Mensagens flash na sessão, exibidas pelo layout |
| XSS | Valores impressos sem escape | Tudo passa por `e()` (`htmlspecialchars`) |
| Datas / moeda | Formato do banco | `dd/mm/aaaa` e `R$ 1.234,56`; fuso `America/Cuiaba` no PHP e no PostgreSQL |

---

## 5. Modelo de dados

```mermaid
erDiagram
    CLIENTES ||--o{ CARRINHOS : "abre"
    CLIENTES ||--o{ VENDAS : "realiza"
    CARRINHOS ||--|{ CARRINHO_ITENS : "contém"
    PRODUTOS ||--o{ CARRINHO_ITENS : "está em"
    CARRINHOS ||--o| VENDAS : "gera"
    VENDAS ||--|{ VENDA_ITENS : "contém"
    PRODUTOS ||--o{ VENDA_ITENS : "vendido em"

    PRODUTOS {
        serial id PK
        varchar cod UK
        varchar nome
        varchar categoria
        date validade "NULL"
        int quantidade "CHECK >= 0"
        numeric preco_unitario
        numeric preco_pacote
        varchar imagem
        boolean ativo
    }
    CLIENTES {
        serial id PK
        varchar nome
        char cpf UK "11 dígitos"
        varchar email
        varchar telefone
        varchar endereco
        date data_nascimento
        boolean ativo
        timestamptz criado_em
        timestamptz atualizado_em
    }
    CARRINHOS {
        serial id PK
        int cliente_id FK "NULL"
        varchar status "aberto|finalizado|cancelado"
        timestamptz criado_em
        timestamptz atualizado_em
    }
    CARRINHO_ITENS {
        serial id PK
        int carrinho_id FK "ON DELETE CASCADE"
        int produto_id FK
        int quantidade
        numeric preco_unitario
    }
    VENDAS {
        serial id PK
        int carrinho_id FK
        int cliente_id FK "NULL"
        varchar forma_pagamento
        numeric subtotal
        numeric desconto
        numeric acrescimo
        numeric total
        numeric valor_recebido
        numeric troco
        varchar status "concluida|cancelada"
        text observacao
        timestamptz criado_em
    }
    VENDA_ITENS {
        serial id PK
        int venda_id FK "ON DELETE CASCADE"
        int produto_id FK
        int quantidade
        numeric preco_unitario
        numeric subtotal
    }
```

- `UNIQUE (carrinho_id, produto_id)` em `carrinho_itens`: adicionar o mesmo produto soma a quantidade.
- Índices: `vendas(criado_em)`, `vendas(cliente_id)`, `venda_itens(produto_id)` e outros auxiliares.
- `schema.sql` é **idempotente** (`CREATE TABLE IF NOT EXISTS`, `ADD COLUMN IF NOT EXISTS`, constraints criadas só se não existirem), pois a tabela `produtos` já existe no Neon.

---

## 6. POP – Procedimento Operacional Padrão de Implantação

| Campo | Conteúdo |
|---|---|
| **Título** | Implantação do sistema PDV MVC (local, Neon e hospedagem cPanel) |
| **Código / versão** | POP-PDV-001 · versão 1.0 |
| **Objetivo** | Padronizar a instalação, configuração, publicação e verificação do sistema PDV MVC, garantindo que qualquer pessoa da equipe consiga colocá-lo em funcionamento com segurança (sem expor credenciais). |
| **Escopo** | Ambiente de desenvolvimento local (Linux/Windows/macOS), banco PostgreSQL no Neon, hospedagem compartilhada cPanel (Apache) e publicação do código no GitHub. |
| **Responsável** | [Seu nome] — desenvolvedor(a) responsável pela implantação. Aprovação: [Professor]. |
| **Frequência** | Na primeira implantação e a cada nova versão publicada. |

### 6.1 Pré-requisitos

- Conta no [Neon](https://neon.tech) (plano gratuito atende) e no [GitHub](https://github.com).
- Acesso ao cPanel da hospedagem (para a etapa D).
- Conhecimento básico de terminal e Git.

### 6.2 Materiais e ferramentas

| Item | Versão mínima | Observação |
|---|---|---|
| PHP | 8.1 | Extensões **`pdo_pgsql`**, `fileinfo`, `mbstring` (recomendada) |
| Git | 2.x | Para clonar e publicar |
| Composer | 2.x | **Opcional** — o projeto tem autoload próprio |
| Navegador | Atual | Chrome, Firefox ou Edge |
| psql | 14+ | Opcional (alternativa ao SQL Editor do Neon) |
| Cliente FTP | — | Opcional (FileZilla) para a hospedagem |

### 6.3 Procedimento

#### A) Ambiente local

1. **Instale o PHP 8.1+ com `pdo_pgsql`**:
   - Ubuntu/Debian: `sudo apt install php-cli php-pgsql php-mbstring git`
   - Windows: baixe o PHP em <https://windows.php.net/download>, e no `php.ini` remova o `;` de `extension=pdo_pgsql`, `extension=pgsql`, `extension=fileinfo` e `extension=mbstring`.
   - macOS: `brew install php` (já inclui `pdo_pgsql`).
2. **Confirme as extensões**: `php -v` (≥ 8.1) e `php -m | grep -i pdo_pgsql` (deve listar `pdo_pgsql`).
3. **Clone o repositório**:
   ```bash
   git clone https://github.com/<seu-usuario>/pdv-mvc.git
   cd pdv-mvc
   ```
4. *(Opcional)* `composer dump-autoload` — gera `vendor/autoload.php`. Sem Composer o sistema usa o autoload de `app/bootstrap.php`.
5. **Crie o `.env`** a partir do modelo:
   ```bash
   cp .env.example .env        # Windows: copy .env.example .env
   ```

#### B) Banco de dados no Neon

6. Acesse o console do Neon → **New Project** → nome `pdv-mvc`, PostgreSQL 16, região **AWS São Paulo (sa-east-1)**.
7. Em **Dashboard → Connect**, marque **Pooled connection** e copie a *connection string* (o host contém `-pooler`), por exemplo:
   `postgresql://neondb_owner:SENHA@ep-xxxx-pooler.sa-east-1.aws.neon.tech/neondb?sslmode=require`
8. Cole no `.env`:
   ```dotenv
   SRVDB="postgresql://neondb_owner:SENHA@ep-xxxx-pooler.sa-east-1.aws.neon.tech/neondb?sslmode=require"
   APP_DEBUG=true            # true só em desenvolvimento
   APP_URL=http://localhost:8000
   ```
9. **Crie as tabelas** — escolha **uma** das opções:
   - **SQL Editor do Neon**: abra o *SQL Editor*, cole o conteúdo de `database/schema.sql` → **Run**; depois cole `database/seed.sql` → **Run**.
   - **psql**: `psql "<connection string>" -f database/schema.sql` e `psql "<connection string>" -f database/seed.sql`
   - **Script do projeto**: `php database/migrate.php --seed`
10. Verifique: `SELECT COUNT(*) FROM produtos;` deve retornar pelo menos 10, e `SELECT COUNT(*) FROM vendas;` pelo menos 8.

#### C) Execução local e testes

11. Suba o servidor embutido apontando para `public/`:
    ```bash
    php -S localhost:8000 -t public
    ```
12. Acesse <http://localhost:8000> e execute o checklist:

| # | Teste | Resultado esperado |
|---|---|---|
| 1 | Abrir **Início** | Cards de vendas do dia, faturamento do mês, estoque baixo e validade |
| 2 | **Produtos** → buscar por parte do nome e por código | Lista filtrada, paginação |
| 3 | **Produtos** → Novo, enviar imagem `.txt` renomeada para `.jpg` | Erro "Formato de imagem não permitido" e campos mantidos |
| 4 | **Produtos** → editar e trocar imagem | Imagem nova exibida; antiga removida |
| 5 | **Produtos** → excluir produto que já tem venda | Aviso de bloqueio; botão "Inativar" funciona |
| 6 | Abrir `index.php?rota=produtos/editar` **sem id** | Página "Produto não encontrado" (sem warnings) |
| 7 | **Clientes** → Novo com CPF `111.111.111-11` | Erro "CPF inválido", demais campos preservados |
| 8 | **Clientes** → cadastrar, visualizar, editar | Dados e histórico de compras exibidos |
| 9 | **Clientes** → excluir cliente com vendas | Cliente apenas inativado |
| 10 | **PDV** → buscar produto e "Adicionar" | Carrinho atual criado/atualizado |
| 11 | **Carrinho** → quantidade maior que o estoque | Erro "Estoque insuficiente" |
| 12 | **Carrinho** → alterar quantidade, remover item, esvaziar | Totais atualizados |
| 13 | **Carrinho** → finalizar em Dinheiro com valor menor que o total | Erro "Valor recebido insuficiente" |
| 14 | **Carrinho** → finalizar em Dinheiro com valor maior | Cupom com desconto de 5% e troco; estoque baixado; linha nova em `storage/logs/vendas.log` |
| 15 | **Relatórios** → filtrar período/cliente/forma/status; trocar tipo | Indicadores, gráficos e tabela atualizados |
| 16 | **Relatórios** → Exportar CSV e abrir no Excel | Acentos corretos, colunas separadas |
| 17 | **Relatórios** → editar forma de pagamento de uma venda | Total recalculado pela nova forma |
| 18 | **Relatórios** → cancelar venda | Status "Cancelada"; estoque devolvido |
| 19 | **Relatórios** → excluir venda cancelada | Venda removida |
| 20 | Enviar um formulário após apagar o cookie de sessão | Mensagem de token CSRF inválido; nada é alterado |

#### D) Implantação em hospedagem cPanel (Apache)

13. **Envie os arquivos** (sem `.env`, `vendor/` e `.git/`) por um dos meios:
    - **Git Version Control** do cPanel: *Create* → cole a URL do repositório → *Clone*.
    - **FTP** (FileZilla) ou **Gerenciador de Arquivos** → *Upload* do `.zip` → *Extract*.
14. **Configure a pasta pública** — escolha a opção disponível:
    - **(Recomendado)** Em *Domínios* (ou *Subdomínios*), defina a **raiz do documento** como `.../pdv-mvc/public`.
    - **Se não puder alterar o DocumentRoot**: coloque o projeto inteiro dentro de `public_html/` (ou de uma subpasta). O `.htaccess` da raiz redireciona tudo para `public/` e bloqueia o acesso ao `.env`.
15. **Crie o `.env` no servidor** (Gerenciador de Arquivos → *+ Arquivo*) na raiz do projeto, com a connection string do Neon e `APP_DEBUG=false`. Ative *Mostrar arquivos ocultos* para vê-lo.
16. **Permissões**: `public/uploads/produtos` e `storage/logs` com **755** (ou **775** se o PHP rodar com outro usuário); arquivos com **644**; `.env` com **600** ou **640**.
17. **Versão do PHP**: em *Select PHP Version* (CloudLinux) ou *MultiPHP Manager*, escolha **8.1 ou superior** e, em *Extensions*, marque **`pdo_pgsql`**, `pgsql`, `fileinfo` e `mbstring` → *Save*.
18. Se o host bloquear conexões de saída na porta **5432**, abra um chamado pedindo a liberação para `*.neon.tech`.
19. Acesse o domínio e repita os itens 1, 2, 10, 14 e 15 do checklist.

#### E) Solução de problemas

| Sintoma | Causa provável | Solução |
|---|---|---|
| `ERROR: Endpoint ID is not specified` | A libpq do servidor é antiga e não envia **SNI**; o proxy do Neon não sabe qual endpoint usar | Já tratado em `Database::montarDsn()`: para hosts `*.neon.tech`, o endpoint (primeira parte do host, ex.: `ep-xxxx-pooler`) vai em `options='endpoint=...'` (num PostgreSQL local/Docker essa opção não é enviada, pois ele a rejeitaria). Confira se a `SRVDB` está completa. Se persistir, teste o endpoint sem o sufixo `-pooler` ou a string *direct* (sem pooler) |
| `could not find driver` | Extensão `pdo_pgsql` desativada | Local: instalar `php-pgsql` / habilitar no `php.ini`. cPanel: *Select PHP Version → Extensions → pdo_pgsql* |
| `SSL connection is required` / erro de SSL | `sslmode` ausente ou libpq sem OpenSSL | Mantenha `?sslmode=require` na `SRVDB` (o DSN usa `require` por padrão); peça ao suporte a libpq com SSL |
| `password authentication failed` | Senha errada ou com caracteres especiais | Gere nova senha no Neon (*Roles → Reset password*); caracteres como `@` e `#` devem estar URL-encoded (`%40`, `%23`) |
| Horários errados (3 ou 4 h de diferença) | Fuso do PHP/PostgreSQL | O sistema define `America/Cuiaba` no PHP (`date_default_timezone_set`) e na sessão do banco (`--timezone=` no DSN). Não altere para UTC |
| "Não foi possível salvar a imagem" | Pasta sem permissão de escrita | `chmod 775 public/uploads/produtos` (cPanel: *Permissões*); veja detalhes em `storage/logs/app.log` |
| Imagem maior que o limite | `upload_max_filesize` do servidor menor que 2 MB | Ajuste `upload_max_filesize=4M` e `post_max_size=8M` em *MultiPHP INI Editor* |
| `Undefined variable $produto` (legado) | Página aberta sem `?id` | O novo código busca o registro no controller e responde 404 quando não existe; todo warning vira exceção registrada no log, então não há HTML quebrado |
| Página em branco / erro 500 genérico | Erro de PHP em produção | Veja `storage/logs/app.log`; em desenvolvimento use `APP_DEBUG=true` |
| 404 em todas as URLs amigáveis | `mod_rewrite` desativado | Use `APP_URL_AMIGAVEL=false` (padrão): links no formato `index.php?rota=...` funcionam sem rewrite |
| Erro ao rodar `schema.sql`: `could not create unique index "produtos_cod_unique"` | Códigos duplicados já existentes em `produtos` | `SELECT cod, COUNT(*) FROM produtos GROUP BY cod HAVING COUNT(*) > 1;` → corrija os duplicados e rode novamente |
| Erro ao rodar `schema.sql`: `check constraint "produtos_quantidade_check" is violated` | Estoque negativo gerado pelo sistema legado | `UPDATE produtos SET quantidade = 0 WHERE quantidade < 0;` e rode novamente |
| Imagens antigas não aparecem | Ficavam em `imagens/` no legado | Copie os arquivos para `public/uploads/produtos/` (o banco guarda só o nome do arquivo) |

#### F) Publicação no GitHub

20. Confira que o `.gitignore` contém `.env`, `vendor/`, `storage/logs/*` e `public/uploads/produtos/*`.
21. Inicialize e faça commits pequenos e descritivos:
    ```bash
    git init -b main
    git add .
    git status                 # o .env NÃO pode aparecer na lista
    git commit -m "feat: estrutura MVC do PDV"
    ```
22. Crie um repositório **vazio** no GitHub (sem README) e envie:
    ```bash
    git remote add origin https://github.com/<seu-usuario>/pdv-mvc.git
    git push -u origin main
    ```
23. **Confira que o `.env` não foi enviado**: `git ls-files | grep -x .env` não deve retornar nada; na página do repositório, o arquivo não pode aparecer.
24. Se uma credencial for enviada por engano: **troque a senha no Neon imediatamente** (o histórico do Git guarda o arquivo mesmo após apagá-lo) e depois remova-o do histórico.

### 6.4 Verificação / critérios de aceite

- [ ] `php -m` lista `pdo_pgsql` no ambiente (local e servidor).
- [ ] `schema.sql` e `seed.sql` executados sem erro; `vendas` com ao menos 8 registros.
- [ ] Os 20 itens do checklist da etapa C passam localmente.
- [ ] Na hospedagem, a página inicial carrega e uma venda completa é finalizada.
- [ ] `https://seu-dominio/.env`, `/app/` e `/legado/` retornam 403/404.
- [ ] `APP_DEBUG=false` em produção.
- [ ] O `.env` **não** está no repositório do GitHub.

### 6.5 Histórico de revisões

| Versão | Data | Autor | Descrição |
|---|---|---|---|
| 1.0 | 09/11/2026 | [Seu nome] | Criação do POP: ambiente local, Neon, cPanel, solução de problemas e GitHub |

---

## 7. Como usar

> Coloque as capturas de tela em `docs/prints/` com os nomes abaixo para que apareçam aqui.

1. **Início** — visão geral do dia e alertas de estoque/validade.
   ![Dashboard](docs/prints/01-dashboard.png)
2. **Produtos** — cadastre/edite produtos; busque por nome ou código.
   ![Produtos](docs/prints/02-produtos.png)
3. **Clientes** — cadastre clientes (CPF validado) e veja o histórico de compras de cada um.
   ![Clientes](docs/prints/03-clientes.png)
4. **PDV / Carrinho** — busque o produto e clique em **Adicionar**; vá ao carrinho, ajuste quantidades, escolha o cliente e a forma de pagamento (a prévia mostra desconto, total e troco) e clique em **Finalizar venda**.
   ![Carrinho](docs/prints/04-carrinho.png)
5. **Cupom** — após finalizar, o cupom é exibido; use **Imprimir**.
   ![Cupom](docs/prints/05-cupom.png)
6. **Relatórios** — filtre por período, cliente, pagamento e status; troque o tipo de relatório; exporte CSV; edite, cancele ou exclua vendas.
   ![Relatórios](docs/prints/06-relatorios.png)

### Regras de pagamento (Strategy)

| Forma | Ajuste | Troco |
|---|---|---|
| Dinheiro | 5% de desconto | Sim (exige valor recebido ≥ total) |
| PIX | 2% de desconto | Não |
| Cartão de débito | — | Não |
| Cartão de crédito | 3% de acréscimo | Não |

---

## 8. Autor e disciplina

- **Autor(a):** [Seu nome]
- **Disciplina:** [Disciplina]
- **Professor(a):** [Professor]
- **Instituição / período:** [Instituição] — [Semestre/Ano]

Projeto acadêmico de uso educacional.
