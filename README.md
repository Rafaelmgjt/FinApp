# 💰 FinApp - Gerenciador de Finanças Pessoais

Uma aplicação web completa para gerenciar suas finanças pessoais de forma simples e eficiente.

## ✨ Funcionalidades

- 👤 **Autenticação de Usuários** - Sistema seguro de login e registro
- 💳 **Gerenciamento de Contas** - Crie e gerenie múltiplas contas bancárias
- 💸 **Registro de Movimentações** - Acompanhe todas as suas transações (receitas e despesas)
- 🔄 **Transferências entre Contas** - Transfira valores entre suas contas
- 📊 **Dashboard** - Visão geral do seu patrimônio financeiro
- 📈 **Relatórios** - Analise seus gastos e receitas
- 👥 **Perfil de Usuário** - Gerencie suas informações pessoais

## 🛠️ Tecnologias

- **Backend**: PHP
- **Frontend**: HTML, CSS, JavaScript
- **Armazenamento**: CSV (dados em arquivos)
- **Containerização**: Docker & Docker Compose

## 📋 Pré-requisitos

- PHP 7.4+
- Docker e Docker Compose (opcional)
- Navegador web moderno

## 🚀 Como Usar

### Instalação Local

1. Clone o repositório:
```bash
git clone https://github.com/Rafaelmgjt/FinApp.git
cd FinApp
```

2. Configure o servidor PHP:
```bash
php -S localhost:8000
```

3. Acesse a aplicação:
```
http://localhost:8000
```

### Com Docker

1. Execute o Docker Compose:
```bash
docker-compose up -d
```

2. Acesse:
```
http://localhost:8000
```

## 📁 Estrutura do Projeto

```
FinApp/
├── config/              # Configurações da aplicação
│   ├── config.php       # Configurações gerais
│   └── database.php     # Configurações do banco de dados
├── includes/            # Arquivos incluídos reutilizáveis
│   ├── header.php       # Cabeçalho da página
│   ├── footer.php       # Rodapé da página
│   └── functions.php    # Funções auxiliares
├── pages/               # Páginas principais
│   ├── login.php        # Página de login
│   ├── registro.php     # Página de registro
│   ├── dashboard.php    # Dashboard principal
│   ├── contas.php       # Gerenciamento de contas
│   ├── movimentacoes.php # Registro de transações
│   ├── transferencias.php # Transferências entre contas
│   ├── relatorios.php   # Relatórios financeiros
│   └── perfil.php       # Perfil do usuário
├── data/                # Arquivos de dados CSV
│   ├── usuarios.csv     # Dados dos usuários
│   ├── contas.csv       # Dados das contas
│   ├── movimentacoes.csv # Histórico de movimentações
│   └── transferencias.csv # Histórico de transferências
├── assets/              # Arquivos estáticos
│   ├── css/             # Folhas de estilo
│   ├── js/              # Scripts JavaScript
│   └── img/             # Imagens
├── index.php            # Página inicial
├── logout.php           # Logout do usuário
└── docker-compose.yml   # Configuração Docker
```

## 👤 Conta de Teste

Você pode usar as seguintes credenciais para testar:

- **Email**: teste@finapp.com
- **Senha**: 123456

## 🔐 Segurança

Este é um projeto de demonstração. Para uso em produção, recomenda-se:

- Implementar validação robusta de entrada
- Usar banco de dados relacional (MySQL, PostgreSQL)
- Aplicar encriptação de senhas
- Implementar HTTPS
- Adicionar proteção contra CSRF e XSS
- Usar variáveis de ambiente para configurações sensíveis

## 📝 Próximas Melhorias

- [ ] Autenticação via Google/GitHub
- [ ] Exportação de relatórios em PDF
- [ ] Gráficos interativos
- [ ] Categorização de movimentações
- [ ] Sistema de orçamento mensal
- [ ] Notificações de transações
- [ ] Backup automático de dados
- [ ] Interface responsiva mobile

## 👨‍💻 Autor

**Rafael Miranda**  
GitHub: [@Rafaelmgjt](https://github.com/Rafaelmgjt)

## 📄 Licença

Este projeto está disponível sob a licença MIT. Veja o arquivo LICENSE para mais detalhes.

## 🤝 Contribuições

Contribuições são bem-vindas! Sinta-se à vontade para abrir issues e pull requests.

---

**Desenvolvido com ❤️ por Rafael Miranda**
