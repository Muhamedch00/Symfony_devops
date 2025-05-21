pipeline {
    agent any
    environment {
        SONAR_TOKEN = credentials('sonar-token')
        DOCKER_IMAGE = "muhamd/symfony-app:${BUILD_NUMBER}"
        SONARQUBE_IP = "172.17.0.1" // Remplacez par l'IP réelle de votre serveur SonarQube
    }
    
    stages {
        stage('Cloner le dépôt') {
            steps {
                git url: 'https://github.com/Muhamedch00/Symfony_devops.git'
            }
        }
        
        stage('Installation des dépendances PHP') {
            steps {
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
                sh '''
                    docker run --rm -v "$PWD":/app -w /app php:8.2-cli bash -c "
                        apt-get update && apt-get install -y git zip unzip libzip-dev
                        docker-php-ext-install zip
                        php -d memory_limit=-1 vendor/bin/phpunit --coverage-clover=coverage.xml
                    "
                '''
            }
        }
        
        stage('Analyse SonarQube') {
            steps {
                catchError(buildResult: 'UNSTABLE', stageResult: 'FAILURE') {
                    withCredentials([
                        string(credentialsId: 'sonar-token', variable: 'SONAR_TOKEN')
                    ]) {
                        sh '''
                            docker run --rm \
                                --add-host=sonarqube:${SONARQUBE_IP} \
                                -v "$PWD":/usr/src \
                                sonarsource/sonar-scanner-cli \
                                -Dsonar.projectKey=SymfonyDevOps \
                                -Dsonar.sources=. \
                                -Dsonar.exclusions=vendor/**,var/**,tests/** \
                                -Dsonar.php.coverage.reportPaths=coverage.xml \
                                -Dsonar.host.url=http://sonarqube:9000 \
                                -Dsonar.login=$SONAR_TOKEN
                        '''
                    }
                    
                    // Vérifier si SonarQube est disponible
                    script {
                        try {
                            timeout(time: 1, unit: 'MINUTES') {
                                waitForQualityGate abortPipeline: true
                            }
                        } catch (Exception e) {
                            echo "Erreur lors de l'attente de la Quality Gate: ${e.message}"
                            currentBuild.result = 'UNSTABLE'
                        }
                    }
                }
            }
        }
        
        stage('Build Docker') {
            steps {
                sh """
                    docker build -t ${DOCKER_IMAGE} .
                """
            }
        }
        
        stage('Push Docker') {
            when {
                expression { 
                    return currentBuild.resultIsBetterOrEqualTo('UNSTABLE')
                }
            }
            steps {
                withCredentials([
                    usernamePassword(
                        credentialsId: 'dockerhub-creds',
                        usernameVariable: 'DOCKER_USER',
                        passwordVariable: 'DOCKER_PASS'
                    )
                ]) {
                    sh '''
                        echo "$DOCKER_PASS" | docker login -u "$DOCKER_USER" --password-stdin
                        docker push $DOCKER_IMAGE
                    '''
                }
            }
        }
        
        stage('Déploiement via Ansible') {
            when {
                expression { 
                    return currentBuild.resultIsBetterOrEqualTo('UNSTABLE')
                }
            }
            steps {
                catchError(buildResult: 'FAILURE', stageResult: 'FAILURE') {
                    sh '''
                        echo "IMAGE=${DOCKER_IMAGE}" > .env
                        ansible-playbook -i inventory.ini deploy.yml
                    '''
                }
            }
        }
    }
    
    post {
        always {
            cleanWs()
        }
        success {
            echo 'Pipeline exécuté avec succès!'
        }
        failure {
            echo 'Le pipeline a échoué. Vérifiez les journaux pour plus de détails.'
        }
        unstable {
            echo 'Le pipeline est instable mais a continué.'
        }
    }
}
