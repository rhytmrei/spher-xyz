pipeline {
    agent any
    environment {
        PATH = "$PATH:/usr/local/bin"
        LOCAL_DIR = '~/Sites/spherxyz/backend'
    }
    stages {
        stage("Verify tooling") {
            steps {
                sh '''
                    echo $PATH
                    docker info
                    docker version
                    docker compose version
                '''
            }
        }
        //
        // ssh -i "~/.ssh/spherxyz-ec2-rsa.pem" ec2-user@ec2-13-60-11-249.eu-north-1.compute.amazonaws.com
        //
        stage("Verify SSH connection to AWS") {
            steps {
                sshagent(credentials: ['aws-spherxyz-ec2']) {
                    sh '''
                        ssh -o StrictHostKeyChecking=no ec2-user@13.60.11.249 whoami
                    '''
                }
            }
        }
        stage("Clear all running docker containers") {
            steps {
                script {
                    try {
                        sh 'docker rm -f $(docker ps -a -q)'
                    } catch (Exception e) {
                        echo 'No running container to clear up...'
                    }
                }
            }
        }
        stage("Populate configuration files") {
            steps {
                sh "cp ${LOCAL_DIR}/.env.production ${workspace}/.env"
                sh "cp ${LOCAL_DIR}/docker/nginx/sites/laravel-prod.conf ${workspace}/docker/nginx/sites/laravel.conf"
                sh "rm ${workspace}/docker/nginx/sites/laravel-prod.conf"
                sh "cp -a ${LOCAL_DIR}/docker/nginx/ssl/* ${workspace}/docker/nginx/ssl/"
            }
        }
        stage("Start Docker") {
            steps {
                sh 'make up'
                sh 'docker ps'
            }
        }
        stage('Initialize the variables') {
            steps{
                script{
                    PHP_FPM_IMAGE=sh(script: "docker ps --filter 'name=php-fpm' --format '{{.Names}}'", returnStdout: true).trim()
                }
            }
        }
        stage("Run Composer Install") {
            steps {
                sh "ls"
                sh "docker exec ${PHP_FPM_IMAGE} composer install"
            }
        }
        stage("Run Tests") {
            steps {
                sh "docker exec ${PHP_FPM_IMAGE} ./artisan test"
            }
        }
    }
    post {
        success {
            sh "cd ${workspace}"
            sh 'rm -rf artifact.zip'
            sh 'zip -r artifact.zip . -x "*node_modules**"'
            withCredentials([sshUserPrivateKey(credentialsId: "aws-spherxyz-ec2", keyFileVariable: 'keyfile')]) {
                sh "scp -v -o StrictHostKeyChecking=no -i ${keyfile} ${workspace}/artifact.zip ec2-user@13.60.11.249:/home/ec2-user/artifact"
            }
            sshagent(credentials: ['aws-spherxyz-ec2']) {
                sh 'ssh -o StrictHostKeyChecking=no ec2-user@13.60.11.249 unzip -o /home/ec2-user/artifact/artifact.zip -d /var/www/html'

                sh 'ssh -o StrictHostKeyChecking=no ec2-user@13.60.11.249 docker-compose -f /var/www/html/docker-compose.yml up -d'

                script {
                    try {
                        sh 'ssh -o StrictHostKeyChecking=no ec2-user@13.60.11.249 sudo chmod 777 /var/www/html/storage -R'
                    } catch (Exception e) {
                        echo 'Some file permissions could not be updated.'
                    }
                }
            }
        }
        always {
            sh 'docker compose down --remove-orphans -v'
            sh 'docker compose ps'
        }
    }
}
