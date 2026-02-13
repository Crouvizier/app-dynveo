<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail Web - DEBUG</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #0a1628 0%, #1e3a5f 100%);
            color: white;
            padding: 2rem;
        }
        .debug {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid #90EE90;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
        }
        .debug h3 {
            color: #90EE90;
            margin-bottom: 0.5rem;
        }
        .debug pre {
            background: rgba(0, 0, 0, 0.3);
            padding: 1rem;
            border-radius: 5px;
            overflow: auto;
            white-space: pre-wrap;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid #90EE90;
            border-radius: 10px;
            padding: 1rem;
        }
        .error { color: #ff6b6b; }
        .success { color: #90EE90; }
    </style>
</head>
<body>
    <h1>🔍 MODE DEBUG - Portail Web</h1>
    
    <div class="debug">
        <h3>📡 Étape 1 : Chargement de la page</h3>
        <pre id="step1">En cours...</pre>
    </div>

    <div class="debug">
        <h3>📡 Étape 2 : Appel API pour les sites</h3>
        <pre id="step2">En attente...</pre>
    </div>

    <div class="debug">
        <h3>📡 Étape 3 : Réponse API brute</h3>
        <pre id="step3">En attente...</pre>
    </div>

    <div class="debug">
        <h3>📡 Étape 4 : Données parsées</h3>
        <pre id="step4">En attente...</pre>
    </div>

    <div class="debug">
        <h3>📡 Étape 5 : Rendu HTML</h3>
        <pre id="step5">En attente...</pre>
    </div>

    <h2>🎨 Résultat final :</h2>
    <div class="grid" id="sites-grid"></div>

    <script>
        const log = (step, message, isError = false) => {
            const el = document.getElementById(step);
            const timestamp = new Date().toLocaleTimeString();
            const className = isError ? 'error' : 'success';
            el.innerHTML += `<span class="${className}">[${timestamp}] ${message}</span>\n`;
            console.log(`[${step}] ${message}`);
        };

        log('step1', '✅ Page chargée');
        log('step1', '✅ JavaScript fonctionne');
        log('step1', `URL actuelle: ${window.location.href}`);

        // Test de l'API
        async function testAPI() {
            log('step2', '🔄 Démarrage de l\'appel API...');
            
            const apiUrl = `${window.location.origin}${window.location.pathname.replace('index-debug.php', '')}api.php?action=list&type=site`;
            log('step2', `URL API: ${apiUrl}`);

            try {
                log('step2', '📤 Envoi de la requête...');
                const response = await fetch(apiUrl);
                
                log('step2', `📥 Réponse reçue - Status: ${response.status}`);
                log('step2', `📥 Headers: ${response.headers.get('content-type')}`);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const responseText = await response.text();
                log('step3', '✅ Texte brut reçu:');
                log('step3', responseText);

                let result;
                try {
                    result = JSON.parse(responseText);
                    log('step4', '✅ JSON parsé avec succès');
                    log('step4', JSON.stringify(result, null, 2));
                } catch (parseError) {
                    log('step4', `❌ Erreur de parsing JSON: ${parseError.message}`, true);
                    log('step4', `Contenu reçu: ${responseText}`, true);
                    return;
                }

                if (result.success) {
                    log('step4', `✅ Succès ! Nombre de sites: ${result.data.length}`);
                    
                    if (result.data.length === 0) {
                        log('step5', '⚠️ Aucun site retourné par l\'API');
                        document.getElementById('sites-grid').innerHTML = '<p>Aucun site trouvé</p>';
                    } else {
                        log('step5', '🎨 Génération du HTML...');
                        renderSites(result.data);
                        log('step5', `✅ ${result.data.length} site(s) affiché(s)`);
                    }
                } else {
                    log('step4', `❌ API retourne success=false`, true);
                    log('step4', `Erreur: ${result.error || 'Inconnue'}`, true);
                }

            } catch (error) {
                log('step2', `❌ ERREUR: ${error.message}`, true);
                log('step2', `Stack: ${error.stack}`, true);
            }
        }

        function renderSites(sites) {
            const grid = document.getElementById('sites-grid');
            grid.innerHTML = '';

            sites.forEach(site => {
                const card = document.createElement('div');
                card.className = 'card';
                card.innerHTML = `
                    <h3>${site.name}</h3>
                    <p><strong>URL:</strong> ${site.url}</p>
                    <p><strong>CMS:</strong> ${site.cms} ${site.version || ''}</p>
                    <p><strong>Description:</strong> ${site.description.substring(0, 100)}...</p>
                    <p><strong>Image:</strong> ${site.image_path || 'Aucune'}</p>
                    <p><strong>Type:</strong> ${site.type}</p>
                    <p><strong>ID:</strong> ${site.id}</p>
                `;
                grid.appendChild(card);
                
                log('step5', `✅ Card créée pour: ${site.name}`);
            });
        }

        // Lancer le test au chargement
        log('step1', '🚀 Lancement du test API...');
        testAPI();
    </script>
</body>
</html>
