pipeline {
  agent any

  environment {
    SONAR_TOKEN   = credentials('sonar-token')
    DOCKER_IMAGE  = "muhamd/symfony-app:${BUILD_NUMBER}"
    SONARQUBE_URL = "http://10.0.2.15:9000"
  }

  stages {
    stage('Cloner le dépôt') {
      steps {
        echo "🛎 Clonage du dépôt Symfony DevOps"
        git url: 'https://github.com/Muhamedch00/Symfony_devops.git'
      }
    }

    stage('Installation des dépendances PHP') {
      steps {
        echo "📦 Installation des dépendances avec Composer"
        sh '''
          docker run --rm -v "$PWD":/app -w /app composer:2 sh -c "
            git config --global --add safe.directory /app &&
            composer install --no-interaction --prefer-dist
          "
        '''
      }
    }

    stage('Tests Unitaires et Couverture') {
      steps {
        echo "🧪 Lancement des tests"
        sh '''
          docker run --rm -v "$PWD":/app -w /app php:8.2-cli bash -c "
            apt-get update && apt-get install -y git zip unzip libzip-dev &&
            docker-php-ext-install zip &&
            php -d memory_limit=-1 vendor/bin/phpunit --coverage-clover=coverage.xml
          "
        '''
      }
    }

    stage('Analyse SonarQube') {
      steps {
        echo "📊 Analyse SonarQube"
        catchError(buildResult: 'UNSTABLE', stageResult: 'FAILURE') {
          sh '''
            docker run --rm \
              -v "$PWD":/usr/src \
              sonarsource/sonar-scanner-cli \
              -Dsonar.projectKey=SymfonyDevOps \
              -Dsonar.sources=. \
              -Dsonar.exclusions=vendor/**,var/**,tests/** \
              -Dsonar.php.coverage.reportPaths=coverage.xml \
              -Dsonar.host.url=${SONARQUBE_URL} \
              -Dsonar.login=${SONAR_TOKEN}
          '''

          script {
            try {
              timeout(time: 1, unit: 'MINUTES') {
                waitForQualityGate abortPipeline: true
              }
            } catch (Exception e) {
              echo "Erreur Quality Gate: ${e.message}"
              currentBuild.result = 'UNSTABLE'
            }
          }
        }
      }
    }

    stage('Démarrer les services Docker') {
      steps {
        echo "🚀 Lancement des services Symfony + Monitoring"
        sh '''
          docker compose down || true
          docker compose up -d --build
          docker compose ps
        '''
      }
    }

    stage('Build Docker Image') {
      steps {
        echo "🐳 Construction de l’image Docker"
        sh "docker build -t ${DOCKER_IMAGE} ."
      }
    }

    stage('Push Docker Image') {
      when {
        expression { return currentBuild.resultIsBetterOrEqualTo('UNSTABLE') }
      }
      steps {
        echo "📤 Push sur Docker Hub"
        withCredentials([usernamePassword(
          credentialsId: 'dockerhub-creds',
          usernameVariable: 'DOCKER_USER',
          passwordVariable: 'DOCKER_PASS'
        )]) {
          sh '''
            echo "$DOCKER_PASS" | docker login -u "$DOCKER_USER" --password-stdin
            docker push ${DOCKER_IMAGE}
          '''
        }
      }
    }

    stage('Déploiement Ansible') {
      when {
        expression { return currentBuild.resultIsBetterOrEqualTo('UNSTABLE') }
      }
      steps {
        echo "🚀 Déploiement via Ansible"
        sh '''
          echo "IMAGE=${DOCKER_IMAGE}" > .env
          ansible-playbook -i inventory.ini deploy.yml
        '''
      }
    }

    stage('Vérification Monitoring') {
      steps {
        echo "⏳ Vérification de Grafana & Prometheus"
        sh 'sleep 30'
        script {
          def prometheusStatus = sh(script: 'curl -s -o /dev/null -w "%{http_code}" http://10.0.2.15:9090', returnStdout: true).trim()
          def grafanaStatus    = sh(script: 'curl -s -o /dev/null -w "%{http_code}" http://10.0.2.15:3001', returnStdout: true).trim()

          if (prometheusStatus != "200") {
            error "❌ Prometheus ne répond pas (HTTP ${prometheusStatus})"
          } else {
            echo "✅ Prometheus est UP"
          }

          if (grafanaStatus != "200") {
            error "❌ Grafana ne répond pas (HTTP ${grafanaStatus})"
          } else {
            echo "✅ Grafana est UP"
          }
        }
      }
    }
  }

  post {
    always {
      echo '📋 Nettoyage du workspace'
      cleanWs()
    }
    success {
      echo '✅ Pipeline exécutée avec succès.'
    }
    failure {
      echo '❌ Le pipeline a échoué.'
    }
    unstable {
      echo '⚠️ Le pipeline est instable.'
    }
  }
}
