## Отчёты по исследованиям, разработанные по заданию заказчика 

### Основные функции 
1. Специфические настройки отчётов находятся в config.json (могут быть транслированы в интерфейс админки)
2. Controller->getSummaryReport() выводит сводную информацию по указанным в конфиге исследованиям с разбиением по месту пребывания пациента с возможностью выбора данных для конкретного исполнителя
3. Controller->getPatientData() выводит данные по исследованиям в разрезе пациентов с объединением по виду исследования с возможностью выбора данных для конкретного исполнителя и вида исследования
4. Controller->getLabJournalData() выводит настраиваемый отчёт по указанным в конфиге параметрам лабораторного исследования с целью поместить информацию на ширину листа А4



