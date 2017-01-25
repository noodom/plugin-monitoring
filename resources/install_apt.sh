touch /tmp/dependancy_monitoring_in_progress
echo 0 > /tmp/dependancy_monitoring_in_progress
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
apt-get update
echo 50 > /tmp/dependancy_monitoring_in_progress
apt-get install --yes --force-yes php5-snmp
echo 100 > /tmp/dependancy_monitoring_in_progress
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm /tmp/dependancy_monitoring_in_progress