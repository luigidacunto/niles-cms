# Politica di sicurezza

## Versioni supportate

Gli aggiornamenti di sicurezza riguardano l'ultima versione rilasciata sul ramo `main`. Chi usa NILES in
produzione è invitato a restare aggiornato.

## Segnalare una vulnerabilità

**Non aprire una issue pubblica** e non descrivere il problema in discussioni aperte.

Usa la segnalazione privata di GitHub: nella pagina del repository, scheda **Security** →
**Report a vulnerability**. Se non è disponibile, apri una issue che chieda soltanto un canale di contatto
riservato, senza dettagli tecnici.

Nella segnalazione indica, se possibile:

- la versione di NILES (campo `version` in `config/app.php`) e l'ambiente (PHP, database, server web);
- una descrizione del problema e dell'impatto;
- i passaggi per riprodurlo o una prova di concetto, senza usarla su installazioni altrui.

## Cosa aspettarsi

- Una conferma di ricezione appena possibile; il progetto ha un solo mantenitore, quindi i tempi dipendono dalla
  sua disponibilità.
- La valutazione della gravità e, se confermata, una correzione rilasciata il prima possibile.
- Il riconoscimento di chi ha segnalato, se lo desidera, una volta pubblicata la correzione.

Chiediamo di concedere un tempo ragionevole per correggere prima di divulgare pubblicamente i dettagli.

## Per chi installa NILES

- Cambia subito la password dell'amministratore creato dal seeder iniziale.
- In produzione usa `APP_DEBUG=false` e un vero server SMTP per l'invio dei codici di accesso.
- Mantieni aggiornati PHP, le dipendenze (`composer update`) e il database, ed esegui i backup (`scripts/backup.sh`).
- Il file `.env` contiene segreti: non deve essere raggiungibile dal web né finire in un repository.
