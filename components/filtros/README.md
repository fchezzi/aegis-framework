# Componente Filtros - Guia de Uso

## Visão Geral
Componente de filtros dinâmicos que suporta dois tipos: **Canal** e **Data**.

## Novidade: Múltiplos Grupos v2.1.0

### O que é?
Agora é possível configurar um filtro de canal para afetar **múltiplos grupos de cards simultaneamente**.

### Quando usar?
Use quando você quer:
- **UM filtro de canal único** que afeta toda a página
- **Múltiplos filtros de data diferentes** (cada um controlando sua própria seção)

### Como configurar?

#### Exemplo Prático:

```
FILTRO CANAL:
  filter_group: "website,youtube,instagram"
  filter_type: "canal"
  platform: "youtube"

FILTRO MÊS/ANO (Seção Website):
  filter_group: "website"

FILTRO DATA (Seção YouTube):
  filter_group: "youtube"

FILTRO MÊS/ANO (Seção Instagram):
  filter_group: "instagram"

CARDS WEBSITE:
  filter_group: "website"
  → Recebem: Canal + Mês/Ano da seção website

CARDS YOUTUBE:
  filter_group: "youtube"
  → Recebem: Canal + Data da seção youtube

CARDS INSTAGRAM:
  filter_group: "instagram"
  → Recebem: Canal + Mês/Ano da seção instagram
```

### Resultado:
- Ao mudar o **canal**, TODOS os cards de todas as seções atualizam
- Ao mudar **Mês/Ano da seção Website**, apenas cards do grupo "website" atualizam
- Ao mudar **Data da seção YouTube**, apenas cards do grupo "youtube" atualizam
- Cada seção mantém seu próprio filtro de data independente

## Configuração de Campos

### filter_group
- **Tipo:** text
- **Obrigatório:** sim
- **Múltiplos grupos:** Separe por vírgula sem espaços
- **Exemplos:**
  - `"website"` → Grupo único
  - `"grupo1,grupo2,grupo3"` → Múltiplos grupos

### filter_type
- **Tipo:** select
- **Opções:** `canal` ou `data`
- **Obrigatório:** sim

### platform
- **Tipo:** select
- **Opções:** youtube, tiktok, instagram, facebook, website
- **Quando usar:** Apenas para `filter_type = "canal"`

### show_presets
- **Tipo:** select
- **Opções:** yes, no
- **Quando usar:** Apenas para `filter_type = "data"`
- **O que faz:** Mostra botões de atalho (Últimos 7/30/90 dias, etc)

## Arquivos Relacionados

- **Filtros.php** - Componente principal
- **filtros-fix.js** - Fix para preservar filtros de data ao mudar canal
- **filter-mesano.js** - Lógica do filtro mês/ano
- **filtros-autoload.js** - Auto-aplicação de filtros padrão

## Versão
- **Atual:** 2.1.0
- **Novidade:** Suporte a múltiplos grupos
- **Data:** 18/12/2024
