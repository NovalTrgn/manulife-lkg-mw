```mermaid
graph TD
    classDef bw fill:#ffffff,stroke:#000000,stroke-width:1.5px,color:#000000;

    Owner["<b>KASTERIA GINTING</b><br>GA Owner LKG MW"]:::bw

    Admin1["<b>DWY</b><br>Admin"]:::bw
    Admin2["<b>TABITA</b><br>Admin"]:::bw

    SBP1["<b>MINARIA FLORA</b><br>SBP"]:::bw
    SBP2["<b>ANATASYA FALTY</b><br>SBP"]:::bw
    SBP3["<b>IFNUL FANDYKA</b><br>SBP"]:::bw
    SBP4["<b>BUKIT PARLINGGOMAN</b><br>SBP"]:::bw
    SBP5["<b>ERIKA NATALYN</b><br>SBP"]:::bw
    SBP6["<b>BETA SIANIPAR</b><br>SBP"]:::bw

    BP1["Tim Business Partner<br>(BP)"]:::bw
    BP2["Tim Business Partner<br>(BP)"]:::bw
    BP3["Tim Business Partner<br>(BP)"]:::bw
    BP4["Tim Business Partner<br>(BP)"]:::bw
    BP5["Tim Business Partner<br>(BP)"]:::bw
    BP6["Tim Business Partner<br>(BP)"]:::bw

    Owner --> Admin1
    Owner --> Admin2

    Admin1 --> SBP1
    Admin1 --> SBP2
    Admin1 --> SBP3
    Admin2 --> SBP4
    Admin2 --> SBP5
    Admin2 --> SBP6

    SBP1 --> BP1
    SBP2 --> BP2
    SBP3 --> BP3
    SBP4 --> BP4
    SBP5 --> BP5
    SBP6 --> BP6
```