PROGRESS_FILE=/tmp/dependancy_monitoring_in_progress
if [ ! -z $1 ]; then
	PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
apt-get update
echo 50 > ${PROGRESS_FILE}
apt-get install --yes --force-yes php-snmp
if [ $? -ne 0 ]; then
	apt-get install --yes --force-yes php5-snmp
fi
echo 75 > ${PROGRESS_FILE}
service apache2 restart
echo 100 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}