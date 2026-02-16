<?php
/**
 * SEOAnalyzer - Analisador de qualidade SEO
 *
 * Responsabilidades:
 * - Calcular score SEO (0-100)
 * - Analisar campos e retornar problemas/sugestões
 * - Validar tamanhos ideais (title, description)
 * - Verificar completude dos dados
 *
 * @version 1.0.0
 */

class SEOAnalyzer {

    /**
     * Calcular score SEO de 0 a 100
     *
     * @param array $data Dados SEO da página
     * @return int Score de 0 a 100
     */
    public static function score($data) {
        $score = 0;

        // 1. SEO Title (30 pontos)
        $titleScore = self::analyzeTitleScore($data['seo_title'] ?? '');
        $score += $titleScore;

        // 2. SEO Description (30 pontos)
        $descScore = self::analyzeDescriptionScore($data['seo_description'] ?? '');
        $score += $descScore;

        // 3. Open Graph completo (20 pontos)
        // Se OG customizado preenchido = +20
        // Se OG vazio MAS tem SEO básico (usa fallback) = +15
        $ogComplete = !empty($data['seo_og_title']) && !empty($data['seo_og_description']);
        $hasSeoBasic = !empty($data['seo_title']) && !empty($data['seo_description']);

        if ($ogComplete) {
            $score += 20;
        } elseif ($hasSeoBasic) {
            $score += 15; // Usa fallback do SEO básico
        } elseif (!empty($data['seo_og_title']) || !empty($data['seo_og_description'])) {
            $score += 10; // Parcial
        }

        // 4. Twitter Card completo (10 pontos)
        // Se Twitter customizado preenchido = +10
        // Se Twitter vazio MAS tem SEO básico (usa fallback) = +7
        $twitterComplete = !empty($data['seo_twitter_title']) && !empty($data['seo_twitter_description']);

        if ($twitterComplete) {
            $score += 10;
        } elseif ($hasSeoBasic) {
            $score += 7; // Usa fallback do SEO básico
        } elseif (!empty($data['seo_twitter_title']) || !empty($data['seo_twitter_description'])) {
            $score += 5; // Parcial
        }

        // 5. Canonical URL definido (5 pontos)
        // Se canonical customizado = +5
        // Se vazio MAS tem slug (usa fallback) = +3
        if (!empty($data['seo_canonical_url'])) {
            $score += 5;
        } elseif (!empty($data['slug'])) {
            $score += 3; // Usa fallback automático
        }

        // 6. OG Image definida (5 pontos)
        if (!empty($data['seo_og_image'])) {
            $score += 5;
        }

        return min(100, $score); // Garantir máximo de 100
    }

    /**
     * Analisar dados SEO e retornar array com problemas e sugestões
     *
     * @param array $data Dados SEO da página
     * @return array Array com análise completa
     */
    public static function analyze($data) {
        $analysis = [
            'score' => self::score($data),
            'grade' => self::getGrade(self::score($data)),
            'issues' => [],
            'suggestions' => []
        ];

        // Analisar Title
        $titleAnalysis = self::analyzeTitle($data['seo_title'] ?? '');
        if (!$titleAnalysis['ok']) {
            $analysis['issues'][] = $titleAnalysis['message'];
        }
        if (!empty($titleAnalysis['suggestion'])) {
            $analysis['suggestions'][] = $titleAnalysis['suggestion'];
        }

        // Analisar Description
        $descAnalysis = self::analyzeDescription($data['seo_description'] ?? '');
        if (!$descAnalysis['ok']) {
            $analysis['issues'][] = $descAnalysis['message'];
        }
        if (!empty($descAnalysis['suggestion'])) {
            $analysis['suggestions'][] = $descAnalysis['suggestion'];
        }

        // Analisar Open Graph
        if (empty($data['seo_og_title']) || empty($data['seo_og_description'])) {
            $analysis['issues'][] = 'Open Graph incompleto (prejudica compartilhamento em redes sociais)';
            $analysis['suggestions'][] = 'Preencha os campos OG Title e OG Description';
        }

        // Analisar Twitter Card
        if (empty($data['seo_twitter_title']) || empty($data['seo_twitter_description'])) {
            $analysis['suggestions'][] = 'Considere preencher Twitter Card para melhor aparência no X/Twitter';
        }

        // Analisar Canonical
        if (empty($data['seo_canonical_url'])) {
            $analysis['suggestions'][] = 'Defina uma URL canônica para evitar conteúdo duplicado';
        }

        // Analisar Imagem
        if (empty($data['seo_og_image'])) {
            $analysis['suggestions'][] = 'Adicione uma imagem para compartilhamento social (recomendado: 1200x630px)';
        }

        return $analysis;
    }

    /**
     * Calcular score do Title (0-30 pontos)
     */
    private static function analyzeTitleScore($title) {
        if (empty($title)) {
            return 0;
        }

        $length = mb_strlen($title);

        // Tamanho ideal: 50-60 caracteres
        if ($length >= 50 && $length <= 60) {
            return 30; // Perfeito
        }

        // Tamanho aceitável: 40-70 caracteres
        if ($length >= 40 && $length <= 70) {
            return 20; // Bom
        }

        // Muito curto ou muito longo
        if ($length < 40 || $length > 70) {
            return 10; // Precisa melhorar
        }

        return 5; // Tem algo, mas longe do ideal
    }

    /**
     * Calcular score da Description (0-30 pontos)
     */
    private static function analyzeDescriptionScore($description) {
        if (empty($description)) {
            return 0;
        }

        $length = mb_strlen($description);

        // Tamanho ideal: 150-160 caracteres
        if ($length >= 150 && $length <= 160) {
            return 30; // Perfeito
        }

        // Tamanho aceitável: 120-160 caracteres
        if ($length >= 120 && $length <= 160) {
            return 20; // Bom
        }

        // Muito curto ou muito longo
        if ($length < 120 || $length > 160) {
            return 10; // Precisa melhorar
        }

        return 5; // Tem algo, mas longe do ideal
    }

    /**
     * Analisar Title detalhadamente
     */
    private static function analyzeTitle($title) {
        if (empty($title)) {
            return [
                'ok' => false,
                'message' => 'SEO Title vazio (crítico)',
                'suggestion' => 'Crie um título otimizado com 50-60 caracteres'
            ];
        }

        $length = mb_strlen($title);

        if ($length < 30) {
            return [
                'ok' => false,
                'message' => 'SEO Title muito curto (' . $length . ' caracteres)',
                'suggestion' => 'Aumente para pelo menos 40 caracteres'
            ];
        }

        if ($length > 70) {
            return [
                'ok' => false,
                'message' => 'SEO Title muito longo (' . $length . ' caracteres, será cortado no Google)',
                'suggestion' => 'Reduza para 50-60 caracteres'
            ];
        }

        if ($length >= 50 && $length <= 60) {
            return [
                'ok' => true,
                'message' => 'SEO Title perfeito (' . $length . ' caracteres)'
            ];
        }

        return [
            'ok' => true,
            'message' => 'SEO Title aceitável (' . $length . ' caracteres)',
            'suggestion' => 'Ideal seria entre 50-60 caracteres'
        ];
    }

    /**
     * Analisar Description detalhadamente
     */
    private static function analyzeDescription($description) {
        if (empty($description)) {
            return [
                'ok' => false,
                'message' => 'SEO Description vazia (crítico)',
                'suggestion' => 'Crie uma descrição atrativa com 150-160 caracteres'
            ];
        }

        $length = mb_strlen($description);

        if ($length < 70) {
            return [
                'ok' => false,
                'message' => 'SEO Description muito curta (' . $length . ' caracteres)',
                'suggestion' => 'Aumente para pelo menos 120 caracteres'
            ];
        }

        if ($length > 160) {
            return [
                'ok' => false,
                'message' => 'SEO Description muito longa (' . $length . ' caracteres, será cortada no Google)',
                'suggestion' => 'Reduza para 150-160 caracteres'
            ];
        }

        if ($length >= 150 && $length <= 160) {
            return [
                'ok' => true,
                'message' => 'SEO Description perfeita (' . $length . ' caracteres)'
            ];
        }

        return [
            'ok' => true,
            'message' => 'SEO Description aceitável (' . $length . ' caracteres)',
            'suggestion' => 'Ideal seria entre 150-160 caracteres'
        ];
    }

    /**
     * Obter conceito baseado no score
     */
    private static function getGrade($score) {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        return 'F';
    }

    /**
     * Obter descrição do conceito
     */
    public static function getGradeDescription($grade) {
        $descriptions = [
            'A+' => 'Excelente! SEO otimizado.',
            'A'  => 'Muito bom! Pequenos ajustes podem melhorar.',
            'B'  => 'Bom, mas há espaço para melhorias.',
            'C'  => 'Regular. Recomenda-se otimizar.',
            'D'  => 'Abaixo do ideal. Precisa de atenção.',
            'F'  => 'Crítico! SEO precisa ser configurado.'
        ];

        return $descriptions[$grade] ?? '';
    }
}
