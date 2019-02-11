<?php
return [
    'article_name_option_fixes' => [
        'prisma-blau'       => 'blau-prisma',
        'prisma-chrom'      => 'chrom-prisma',
        'prisma-gold'       => 'gold-prisma',
        'prisma-regenbogen' => 'regenbogen-prisma',
        'prisma-rot'        => 'rot-prisma',
        'prisma-gunmetal'   => 'gunmetal-prisma',
        '0,4 Ohm'           => '0,4',
        'matt-schwarz'      => 'matt schwarz',
        'schwarz-weiß'      => 'schwarz-weiss',
        'weiß'              => 'weiss',
        '50PG / 50VG'       => [
            '50PG/50VG',
            '50VG/50PG',
            '50VG / 50PG',
        ],
        '28 GA'             => '28GA',
        '26 GA'             => '26GA',
        '24 GA'             => '24GA',
        '22 GA'             => '22GA',
        '24 GA*2+32 GA'     => '24GA*2+32GA',
        '28 GA*2+32 GA'     => '28GA*2+32GA',
        '26 GA+32 GA'       => '26GA+32GA',
    ],

    'articles_without_brand' => [
        'Tasche mit Schulterriemen',
        'Tasche mit Griff',
        'Multifunktionstasche',
        'Keramik Multifunktionspinzette Tweezer V8 schwarz',
        'Celluloid Mundstücke  (5 Stück pro Packung)',
        'Edelstahl Mundstücke S1Z (5 Stück pro Packung)',
        'Kunststoff Mundstücke  (5 Stück pro Packung)',
    ],

    'article_codes' => [],
    'article_names' => [
        'Vampire Vape Applelicious - E-Zigaretten Liquid' => 'Vampire Vape - Applelicious - E-Zigaretten Liquid',
    ],

    'name_prepare' => [
        'preg_replace' => [
            '~\s+~'          => ' ',
            '~(- )+~'        => '$1',
            '~ ,~'           => ',',
            '~, -~'          => '-',
            '~ - ?$~'        => '',
            '~0ml\/ml~'      => '0mg/ml',
            '~(\d+)m~'       => '$1 m',
            '~(0 mg)$~'      => '$1/ml',
            '~(\d) mAH~'     => '$1 mAh',
            '~(\d mg)[^\/]~' => '$1/ml',
            '~Sherbert~'     => 'Sherbet',
            '~Americas~'     => 'America\'s',
            '~(Heads )+~'    => '$1',
            '~(Head )+~'     => '$1',
        ],
    ],

    'name_cleanup' => [
        'preg_replace' => [
            '~\s+~'            => ' ',
            '~(- )+~'          => '$1',
            '~ ,~'             => ',',
            '~, -~'            => '-',
            '~ - ?$~'          => '',
            '~ $~'             => '',
            '~^ ~'             => '',
            '~-V12~'           => '- V12',
            '~-(Ersatz)~'      => '- $1',
            '~-(E-Zigarette)~' => '- $1',
            '~- (mit Ello)~'   => '$1',
            '~-(V8-)~'         => '- $1',
        ],
    ],

    'article_name_replacements' => [
        'preg_replace' => [
            '~Aster$~'                                                   => 'Aster 75 Watt',
            '~((1 Liter)|(\d+ ml)) (Basis)~'                             => '$4 - $1',
            '~1 Liter~'                                                  => '1.000 ml',
            '~E-Zigaretten (Liquid)~'                                    => '- $1',
            '~(Liquid) für E-Zigaretten~'                                => '$1',
            '~Aroma (- Liquid)~'                                         => '$1',
            '~(Shortz.*) - (0 mg/ml)~'                                   => '$1 - 50 ml, $2',
            '~(Koncept XIX.*) (0 mg/ml)~'                                => '$1 - 50 ml, $2',
            '~EZ.WATT~'                                                  => 'EZ.Watt',
            '~-(\d+) ml~'                                                => '- $1 ml',
            '~ (\d+) mAh~'                                               => ' - $1 mAh',
            '~(\d{2})VG */ *(\d{2})PG~'                                  => '- VG/PG: $1:$2,',
            '~(\d{2})PG */ *(\d{2})VG~'                                  => '- VG/PG: $2:$1,',
            '~(\d)(\d{3}) mAh~'                                          => '$1.$2 mAh',
            '~([^,\-]) (\d) m$~'                                         => '$1 - $2 m',
            '~([^,\-]) (\d,\d+) Ohm~'                                    => '$1 - $2 Ohm',
            '~, (\d,\d+ Ohm) Heads?~'                                    => ' Heads, $1',
            '~ (\d+) Watt~'                                              => ' - $1 Watt',
            '~ml - (\d)~'                                                => 'ml, $1',
            '~([^,\-]) (\d(,\d+)?) ?ml~'                                 => '$1, $2 ml',
            '~(\d+)ML(.*Leerflasche)~'                                   => '$2, $1 ml',
            '~([^,\-]) (\d+) ml$~'                                       => '$1 - $2 ml',
            '~([^,\-]) (\d+) mg\/ml$~'                                   => '$1 - $2 mg/ml',
            '~(0 mg/ml) - (\d+ ml)~'                                     => '$2, $1',
            '~(\d+ ml) - (\d+ mg/ml)~'                                   => '$1, $2',
            '~(\d+ mg/ml),? (\d+ ml)~'                                   => '$2, $1',
            '~([^,\-]) (\d+ ml, \d+ mg/ml)~'                             => '$1 - $2',
            '~(Treib.*100 ml$)~'                                         => '$1, 0 mg/ml',
            '~Rebelz (- Aroma)(.*)(- \d+ ml)~'                           => 'Rebelz - $2 $1 $3',
            '~Vape( -)?( Aroma)(.*)(- \d+ ml)~'                          => 'Vape - $3 -$2 $4',
            '~(Vampire Vape) ([^\-])~'                                   => '$1 - $2',
            '~(VLADS VG) Liquid (-.*)~'                                  => '$1 $2 - Liquid',
            '~(Bull) (- Aroma)(.*)(- \d+ ml)~'                           => '$1 - $3 $2 $4',
            '~(SC -) (Aroma) (.*)~'                                      => '$1 $3 - $2',
            '~- Twisted (Aroma) - (.*)(- \d+ ml)~'                       => '- $2 - $1 $3',
            '~(I VG - )(Aroma) (.*)(- \d+ ml)~'                          => '$1 $3 - $2 $4',
            '~(Bozz Liquids -) (Aroma)(.*)(- \d+ ml)~'                   => '$1 $3 - $2 $4',
            '~(Flavorist -) (Aroma)(.*)(- \d+ ml)~'                      => '$1 $3 - $2 $4',
            '~(VapeTastic -) (Aroma) - (.*)(- \d+ ml)~'                  => '$1 $3 - $2 $4',
            '~(Twisted -) (Cryostasis|Road Trip) (Aroma)(.*)(- \d+ ml)~' => '$1 $2 $4 - $3 $5',
            '~((SC)|(InnoCigs))(.*)((- )?(Liquid)|(Aroma))$~'            => '$1$4$6$5 - 10 ml',
            '~(SC) (- Vape Base)(.*-)(.*)~'                              => '$1 $3 - $4',
            '~^(Erste Sahne) ([^\-])~'                                   => '$1 - $2',
            '~(John Smith.*) (- \d+ ml)~'                                => '$1 - Aroma $2',
            '~Heads~'                                                    => 'Head',
            '~(BVC) (Clearomizer) (Head)~'                               => '$1 $3',
            '~((Vape)|(SC)) - (\d+ ml) (Shot) - (.*), (.*)~'             => '$1 - $5 - $6, $4, $7',
            '~(Solt) (\d+ ml) (.*) - (.*), (.*)~'                        => '$1 - $3 - $4, $2, $5',
            '~(Happy Liquid)(.*)~'                                       => '$1$2 - Liquid - 10 ml',
            '~(PlusSolt) (10 ml) (Nikotinsalz) (Shot)  - (\d+ mg/ml)~'   => '$1 $3-$4 - $2, $5',
            '~(.*) (Mignon)(.*) (- .*)~'                                 => '$1 $2 $3 - Akkuzelle $4',
            '~([^\-]) (\d.\d+) (mAh)~'                                   => '$1 - $2 $3',
            '~(Liquid)$~'                                                => '$1 - 10 ml',
            '~(\d+(\.\d+)? ml, 0 mg/ml)$~'                               => '- Shake & Vape - $1',
            '~(Basis -) - Shake \& Vape -~'                              => '$1',
            '~([^,\-]) (\d m)~'                                          => '$1, $2',
            '~Clearomizer (Head)~'                                       => '$1',
            '~(Aspire) (INR) (\d{5})er - (\d\.\d{3} mAh)~'               => '$1 - Akkuzelle - $3, $4',
            '~(iJoy) (21700) Akku - (.*)~'                               => '$1 - Akkuzelle - $2, $3',
            '~EVO~'                                                      => 'Evo',
            '~SINUOUS~'                                                  => 'Sinuous',
            '~POD~'                                                      => 'Pod',
            '~GNOME~'                                                    => 'Gnome',
            '~ Core Dual ~'                                              => ' ',
            '~ Core ~'                                                   => ' ',
            '~Basis~'                                                    => 'Base',
            '~(Afternoon) Vanille-Käsekuchen~'                           => '$1',
            '~(Always) Cola~'                                            => '$1',
            '~(Angels in Heaven) Tabak~'                                 => '$1',
            '~(Blue Spot) Blaubeeren~'                                   => '$1',
            '~(Brown Nutty) Nougat~'                                     => '$1',
            '~(Caribbean) Kokos-Schokoladen~'                            => '$1',
            '~(Celestial Dragon) Tabak~'                                 => '$1',
            '~(Cold Vacci) Heidelbeere-Fresh~'                           => '$1',
            '~(Commander Joe) Tabak~'                                    => '$1',
            '~(Devils Darling) Tabak~'                                   => '$1',
            '~(First Man) Apfel~'                                        => '$1',
            '~(First Money) Orangenlimonade~'                            => '$1',
            '~(Green Angry) Limetten~'                                   => '$1',
            '~(Hairy Fluffy) Pfirsich~'                                  => '$1',
            '~(Inside Red) Wassermelonen~'                               => '$1',
            '~(La Renaissance) Tabak-Schokoladen~'                       => '$1',
            '~(Little Soft) Himbeer~'                                    => '$1',
            '~(Master Wood) Waldmeister~'                                => '$1',
            '~(Milli) Vanille~'                                          => '$1',
            '~(Monkey Around) Bananen-Amarenakirsche~'                   => '$1',
            '~(Pretty Sweetheart) Sahne-Erdbeer~'                        => '$1',
            '~(Red Cyclone) Rote Früchte~'                               => '$1',
            '~(Red Violet) Amarenakirsche~'                              => '$1',
            '~(Rounded Yellow) Honigmelonen~'                            => '$1',
            '~(Spiky) Maracuja~'                                         => '$1',
            '~(Star Spangled) Tabak~'                                    => '$1',
            '~(Sweetheart) Erdbeer~'                                     => '$1',
            '~(The Empire) Tabak Nuss~'                                  => '$1',
            '~(The Rebels) Tabak Vanille~'                               => '$1',
            '~(White Glacier) Fresh~'                                    => '$1',
            '~(Wild West) Tabakaroma~'                                   => '$1',
            '~(Virginias Best) Tabak~'                                   => '$1',
            '~(Strong Taste) Tabak~'                                     => '$1',
            '~(RY4) Tabak~'                                              => '$1',
            '~(Pure) Tabakaroma~'                                        => '$1',
            '~(America\'s Finest) Tabak~'                                => '$1',
            '~E-Zigaretten Nikotinsalz Liquid~'                          => 'Nikotinsalz-Liquid',
            '~E-Zigaretten Starter Set~'                                 => 'E-Zigarette (Set)',
            '~(Anniversary)-(Edition) (E-Zigarette)~'                    => '$1 $2 - $3',
            '~E-Zigaretten Set~'                                         => 'E-Zigarette (Set)',
            '~Clearomizer Set~'                                          => 'Clearomizer (Set)',
            '~Glastank und Mundstück Set~'                               => 'Glastank + Mundstück (Set)',
            '~Tank (Clearomizer)~'                                       => '$1',
            '~Ersatz-Dichtung~'                                          => 'Ersatzdichtung',
            '~Dichtungs-Set~'                                            => 'Ersatzdichtungen',
            '~Heads Heads~'                                              => 'Heads',
            '~AsMODus~'                                                  => 'asMODus',
            '~Nautilus Mini BVC Clearomizer~'                            => 'Nautilus Mini Clearomizer',
            '~mit -~'                                                    => 'mit',
            '~mit D22 ~'                                                 => '',
            '~Pro P~'                                                    => 'pro P',
            '~(pro Pack)[^u]~'                                           => '$1ung)',
            '~St. pro~'                                                  => 'Stück pro',
            '~5er Pack~'                                                 => '5 Stück pro Packung',
            '~10er Packung~'                                             => '(10 Stück pro Packung)',
            '~(Dual Coil), (1,5 Ohm)~'                                   => '- $1, $2',
            '~Vape Base~'                                                => 'Shake & Vape',
            '~mAh 40A~'                                                  => 'mAh, 40 A',
            '~P80 Watt~'                                                 => 'P80 - 80 Watt',
            '~\+ Adapter~'                                               => ', mit Adapter',
            '~Limited Edition - 30 ml~'                                  => 'Limited Edition - Aroma - 30 ml',
            '~(Pod) mit (Head)~'                                         => '$1 inkl. $2',
            '~(6 in 1) Head (Set)~'                                      => 'Coil Set - $1',
            '~Base - Shake \& Vape -~'                                   => 'Base -',
            '~(Head) - (ARC)~'                                           => '$2 $1 -',
            '~(Hookah) (Set)~'                                           => '$1 ($2)',
            '~(Zelos) - 5~'                                              => '$1 - Akku - 5',
            '~(Typhon) (- \d+ Watt)~'                                    => '$1 - Akku $2',
            '~(Speeder) (- \d+ Watt)~'                                   => '$1 - Akkuträger $2',
            '~(SkyStar) (- \d+ Watt)~'                                   => '$1 - Akkuträger $2',
            '~(Puxos) (- \d+ Watt)~'                                     => '$1 - Akkuträger $2',
            '~(Feedlink) (- \d+ Watt)~'                                  => '$1 - Akkuträger $2',
            '~(Dynamo) (- \d+ Watt)~'                                    => '$1 - Akkuträger $2',
            '~(Colossal) (- \d+ Watt)~'                                  => '$1 - Akkuträger $2',
            '~(Minikin.*) (- \d+ Watt)~'                                 => '$1 - Akkuträger $2',
            '~(Lustro) (- \d+ Watt)~'                                    => '$1 - Akkuträger $2',
            '~(Amnis) (- \d+ mAh)~'                                      => '$1 - Akku $2',
            '~(Pumper.*) (- \d+ Watt)~'                                  => '$1 - Squonker Box $2',
            '~(Basal) (Akku)~'                                           => '$1 - $2',
            '~(Invoke) (- \d+ Watt)~'                                    => '$1 - Akkuträger $2',
            '~(iKonn) (- \d+ Watt)~'                                     => '$1 - Akkuträger $2',
            '~(Lexicon) (- \d+ Watt)~'                                   => '$1 - Akkuträger $2',
            '~(iStick .*) (- \d+ Watt)~'                                 => '$1 - Akkuträger $2',
            '~(Aegis Legend) (- \d+ Watt)~'                              => '$1 - Akkuträger $2',
            '~(Nova) (- \d+ Watt)~'                                      => '$1 - Akkuträger $2',
            '~(Espion.*) (- \d+ Watt)~'                                  => '$1 - Akkuträger $2',
            '~([GXTH]-Priv( 2)?) (- \d+ Watt)~'                          => '$1 - Akkuträger $3',
            '~(G-Priv Baby) (- \d+ Watt)~'                               => '$1 - Akkuträger $2',
            '~(X-Priv Baby) (- \d\.\d+ mAh)~'                            => '$1 - Akku $2',
            '~OSUB (King) (- \d+ Watt)~'                                 => 'Osub $1 - Akkuträger $2',
            '~(Mag Baby) (- \d+ Watt)~'                                  => '$1 - Akku $2',
            '~(Ironfist) (- \d+ Watt)~'                                  => '$1 - Akkuträger $2',
            '~(Hypercar) (- \d+ Watt)~'                                  => '$1 - Akkuträger $2',
            '~(Nunchaku) (- \d+ Watt)~'                                  => '$1 - Akkuträger $2',
            '~(Luxe) (- \d+ Watt)~'                                      => '$1 - Akkuträger $2',
            '~(Polar) (- \d+ Watt)~'                                     => '$1 - Akkuträger $2',
            '~(Sinuous.*) (- \d+ Watt)~'                                 => '$1 - Akkuträger $2',
            '~(Revenger.*) (- \d+ Watt)~'                                => '$1 - Akkuträger $2',
            '~(SX Mini.*) (- \d+ Watt)~'                                 => '$1 - Akkuträger $2',
            '~(RX Gen3)~'                                                => 'Reuleaux RX Gen3',
            '~(RX GEN3)~'                                                => 'RX Gen3',
            '~(Reuleaux.*) (- \d+ Watt)~'                                => '$1 - Akkuträger $2',
            '~(CB-80) (- \d+ Watt)~'                                     => '$1 - Akkuträger $2',
            '~(Luxotic.*) (- \d+ Watt)~'                                 => '$1 - Akkuträger $2',
            '~(Dichtung) (Set)~'                                         => 'Ersatzdichtungen',
            '~(Aster)~'                                                  => '$1 - Akkuträger - 75 Watt',
            '~(Head) \((Dual Coil)\)~'                                   => '$2 $1',
            '~(\d,\d+ Ohm) (Head)~'                                      => '$2 - $1',
            '~(iKonn) 220~'                                              => '$1',
            '~(Melo \d) D(\d\d) ((Clearomizer \(Set\))|(Glastank))~'     => '$1 - $3 - $2 mm',
            '~(IM4) - (Head)~'                                           => 'IM4 Head',
            '~HG2 18650~'                                                => 'INR18650HG2 - Akkuzelle',
            '~(VTC((6)|(5A)))~'                                          => '$1 - Akkuzelle',
            '~(INR18650-((25R)|(30Q)))~'                                 => '$1 - Akkuzelle',
            '~\(Panasonic\) (NCR20700B)~'                                => '$1 - Akkuzelle',
            '~(\d{5})er( Akku)?~'                                        => '$1 - Akkuzelle',
            '~Akkuzellebox~'                                             => 'Akkubox',
            '~(Easy 3) (Caps) (.*) \(~'                                  => '$1 $3 - $2 (',
            '~(Sweetheart)e~'                                            => '$1',
            '~(Devil)s (Darling)~'                                       => '$1\'s $2',
            '~(Prince)-((X6)|(M4)|(T10)|(Q4))~'                          => '$1 $2',
            '~(SMOK) (V8)~'                                              => '$1 TF$2',
            '~((vPipe III)|(Zen Pipe)) (Set)~'                           => '$1 - E-Pfeife (Set)',
            '~(Pod) mit Head -~'                                         => '$1 -',
            '~(Pod) mit~'                                                => '$1 -',
            '~(Batterie)-H(ülse)~'                                       => '$1h$2',
            '~(Batterie)-K(appe)~'                                       => '$1k$2',
        ],
    ],
    'product_names'             => [
        'Aspire'    => [
            // Aspire
            '~(Nepho)~',
            '~(Athos)~',
            '~(Atlantis Evo)~',
            '~(PockeX)~',
            '~((Cobble)( AIO)?)~',
            '~((Spryte)( AIO)?)~',
            '~(Proteus)~',
            '~(Typhon)~',
            '~(Speeder)~',
            '~(Puxos)~',
            '~((Triton)( 2)?)~',
            '~((Breeze)( 2)?)~',
            '~((Zelos)( 2.0)?)~',
            '~(Revo( Mini)?)~',
            '~(Nautilus(( 2S?)|( X)|( Mini)|( AIO))?)~',
            '~(Cleito(( EXO)|( 120)?( Pro))?)~',
            '~(SkyStar( Revvo)?)~',
            '~(SkyStar( Revvo)?)~',
            '~(Feedlink)~',
            '~((Revvo)( Mini)?)~',
            '~((K\d)( \& K3)?)~',
        ],
        'asMODus'   => [
            '~(Minikin(( V2 Kodama)|( V2)|( Reborn))?)~',
            '~(C4 RDA)~',
            '~(Colossal)~',
            '~(Lustro)~',
            '~(Nefarius RDTA)~'
        ],
        'SC'        => [
            '~(iJust ((ECM)|(3))?)~',
            '~(Ello ((Vate)|(Duro))?)~',
            '~(Basal)~',
            '~(GS ((Air 2)|(Air)|(Baby))?)~',
            '~(iKonn)~',
            '~(Invoke)~',
            '~(iStick ((Melo)|(Amnis)|(Pico 21700)|(Pico Baby)|(Pico S)|(Pico)|(Trim))?)~',
            '~(iWu)~',
            '~(Lexicon)~',
            '~(Melo \d)~',
            '~(Easy 3)~',
        ],
        'GeekVape'  => [
            '~(Aegis (Legend)?)~',
            '~(Aero Mesh)~',
            '~(Cerberus)~',
            '~(Creed RTA)~',
            '~(Nova)~',
            '~(Zeus Dual RTA)~',
            '~(Loop RDA)~',
        ],
        'Innokin'   => [
            '~(Endura ((T18)|(T20S)|(T22))?)~',
            '~(Prism ((S)|(T18/T22)|(T20)|(T22))?)~',
            '~(Proton)~',
        ],
        'InnoCigs'  => [
            '~(Batpack)~',
            '~(Atopack)~',
            '~(Cubis 2)~',
            '~(eGo AIO)~',
            '~(Espion (Infinite AI)|(Infinite)|(Silk)|(Solo))~',
            '~(eVic Primo Fit)~',
            '~(Exceed (Air)|(Box)|(D19)|(D22)|(Edge))~',
            '~(Notchcore)~',
            '~(Presence)~',
            '~(ProCore (Air Plus)|(Air)|(Conquer)|(Remix)|(X))~',
            '~(Riftcore Duo)~',
            '~(Teros)~',
            '~(Unimax 22)~',
        ],
        'JustFog'   => [
            '~(C601)~',
            '~(Fog1)~',
            '~(J-Easy3)~',
            '~(Minifit)~',
            '~(P16A)~',
            '~(Q16 C)~',
        ],
        'Smok'      => [
            '~(G-Priv ((2)|(Baby)))~',
            '~(Globe)~',
            '~(H-Priv 2)~',
            '~(Mag Baby)~',
            '~(Micro One)~',
            '~(Osub King)~',
            '~(Priv M17)~',
            '~(QBox)~',
            '~(R40)~',
            '~(Stick)~',
            '~(T-Priv)~',
            '~(TFV12 ((Baby Prince)|(Prince))?)~',
            '~(TFV8 ((Baby)|(X-Baby))?)~',
            '~(Baby V2)~'

        ],
        'Steamax'   => [
            // Smok
            '~(G-Priv ((2)|(Baby)))~',
            '~(Globe)~',
            '~(H-Priv 2)~',
            '~(Mag Baby)~',
            '~(Micro One)~',
            '~(Osub King)~',
            '~(Priv M17)~',
            '~(QBox)~',
            '~(R40)~',
            '~(Stick)~',
            '~(T-Priv)~',
            '~(TFV12 ((Baby Prince)|(Prince))?)~',
            '~(TFV8 ((Baby)|(X-Baby))?)~',
            '~(Baby V2)~',

            // Wismec
            '~(Amor NS Pro)~',
            '~(Divider)~',
            '~(Elabo Mini)~',
            '~(Gnome( King)?)~',
            '~(Guillotine V2)~',
            '~(Luxotic BF Box)~',
            '~(Luxotic MF Box)~',
            '~(Luxotic NC)~',
            '~(Motiv 2)~',
            '~(Reuleaux RX Gen3 Dual)~',
            '~(Sinuous(( P80)|( Ravage)|( SW))?)~',
            '~(Reux)~'

        ],
        'Uwell'     => [
            '~(Crown 3)~',
            '~(Fancier RTA)~',
            '~(Hypercar)~',
            '~(Ironfist)~',
            '~(Nunchaku( RDA)?)~',
            '~(Valyrian)~',
            '~(Whirl(( 20)|( 22))?)~',

        ],
        'Vapanion'  => [
            '~(Cascade(( Baby SE)|( Baby)|( One Plus)|( One))?)~',
            '~(NRG( SE)?)~',
            '~(Switcher( LE)?)~'

        ],
        'VapeOnly'  => [
            '~(vPipe III)~',
            '~(Zen Pipe)~',
        ],
        'Vaporesso' => [
            '~(Cascade(( One Plus SE)|( One Plus)|( One)|( Baby SE))?)~',
            '~(Luxe)~',
            '~(NRG)~',
            '~(Orca Solo)~',
            '~(Polar)~',
            '~(Revenger X)~',
            '~(SKRR)~',
            '~(Tarot Baby)~',
            '~(Veco)~',
            '~(Zero)~'
        ],
        'Renova'    => [
            '~(Zero)~'
        ],
        'Wismec'    => [
            '~(Amor NS Pro)~',
            '~(Divider)~',
            '~(Elabo Mini)~',
            '~(Gnome( King)?)~',
            '~(Guillotine V2)~',
            '~(Luxotic BF Box)~',
            '~(Luxotic MF Box)~',
            '~(Luxotic NC)~',
            '~(Motiv 2)~',
            '~(Reuleaux RX Gen3 Dual)~',
            '~(Sinuous(( P80)|( Ravage)|( SW))?)~',
            '~(Reux)~',
        ]
    ],
    'group_names'               => [
        'STAERKE'     => 'Nikotinstärke',
        'WIDERSTAND'  => 'Widerstand',
        'PACKUNG'     => 'Packungsgröße',
        'FARBE'       => 'Farbe',
        'DURCHMESSER' => 'Durchmesser',
        'GLAS'        => 'Glas',
    ],
    'option_names'              => [
        '100PG'                      => '100% PG',
        '100VG'                      => '100% VG',
        '24 GA*2+32 GA'              => '24 GA * 2 + 32 GA',
        '26 GA*3+36 GA'              => '26 GA * 3 + 36 GA',
        '26 GA*2+30 GA'              => '26 GA * 2 + 30 GA',
        '28 GA*2+30 GA'              => '28 GA * 2 + 30 GA',
        '28 GA*2+32 GA'              => '28 GA * 2 + 32 GA',
        '28 GA*3+36 GA'              => '28 GA * 3 + 36 GA',
        '30 GA*3+38 GA'              => '30 GA * 3 + 38 GA',
        '50PG / 50VG'                => 'VG/PG: 50/50',
        '70VG / 30PG'                => 'VG/PG: 70/30',
        '80VG / 20PG'                => 'VG/PG: 80/20',
        'Kompass bronze'             => 'kompass-bronze',
        'Kompass schwarz'            => 'kompass-schwarz',
        'auto pink'                  => 'auto-pink',
        'chrome'                     => 'chrom',
        'dunkler Marmor'             => 'dunkel-marmor',
        'gebürstete bronze'          => 'bronze (gebürstet)',
        'gebürsteter Stahl'          => 'stahl (gebürstet)',
        'gebürstetes schwarz-silber' => 'schwarz-silber (gebürstet)',
        'gebürstetes silber'         => 'silber (gebürstet)',
        'komplett schwarz'           => 'schwarz',
        'metallic grau'              => 'grau (metallic)',
        'metallic-resin'             => 'resin (metallic)',
        'mosaik grau'                => 'grau (mosaik)',
        'mosaik schwarz'             => 'schwarz (mosaik)',
        'mosaik schwarz-weiss'       => 'schwarz-weiß (mosaik)',
        'mosaik weiss'               => 'weiß (mosaik)',
        'mosaik rot'                 => 'rot (mosaik)',
        'regenbogen-gelb'            => 'gelb-regenbogen',
        'regenbogen-schwarz'         => 'schwarz-regenbogen',
        'schwarz / grün sprayed'     => 'schwarz-grün (sprayed)',
        'schwarz / rot sprayed'      => 'schwarz-rot (sprayed)',
        'schwarz / weiss sprayed'    => 'schwarz-weiß (sprayed)',
    ],
    'variant_codes'             => [],
    'manufacturers'             => [
        'Smok'               => [
            'supplier' => 'Smoktech',
            'brand'    => 'Smok'
        ],
        'Renova'             => [
            'supplier' => 'Vaporesso',
            'brand'    => 'Renova',
        ],
        'Dexter`s Juice Lab' => [
            'brand'    => 'Dexter\'s Juice Lab',
            'supplier' => 'Dexter\'s Juice Lab',
        ]
    ],
    'categories'                => [
        'name' => [
            'preg_match' => [
                '~(Akkuzelle)|(Akkubox)~'                            => 'Zubehör > Akkuzellen & Zubehör',
                '~(Leerflasche)|(Squonker Flasche)|(Liquidflasche)~' => 'Zubehör > Squonker- und Leerflaschen',
                '~(Akkuträger)|(Squonker Box)~'                      => 'Akkuträger',
                '~Guillotine V2 - Base~'                             => 'Zubehör > Selbstwickler',
                '~Akku~'                                             => 'Akkus',
                '~(Clearomizer)~'                                    => 'Verdampfer',
                '~(Cartridge)|(Pod)~'                                => 'Pods & Cartridges',
                '~E-Zigarette~'                                      => 'E-Zigaretten',
                '~E-Pfeife~'                                         => 'E-Pfeifen',
                '~(E-Hookah)|(Vaporizer)~'                           => 'Vaporizer',
                '~Aroma~'                                            => 'Aromen',
                '~(Base)|(Shot)~'                                    => 'Basen & Shots',
                '~Liquid~'                                           => 'Liquids',
                '~Easy 3.*Caps~'                                     => 'Liquids > Easy 3 Caps',
                '~Shake & Vape~'                                     => 'Shake & Vape',
                '~Batteriekappe~'                                    => 'Zubehör > Akkuzellen & Zubehör',
                '~Batteriehülse~'                                    => 'Zubehör > Akkuzellen & Zubehör',
                '~Head~'                                             => 'Zubehör > Verdampferköpfe',
                '~(Watte)|(Wickeldraht)|(Coil)~'                     => 'Zubehör > Selbstwickler',
                '~(Ladegerät)|(DigiCharger)~'                        => 'Zubehör > Ladegeräte',
                '~([Tt]asche)|(Lederschale)~'                        => 'Zubehör > Taschen',
                '~(Werkzeug)|(pinzette)|(Heizplatte)~'               => 'Zubehör > Werkzeug',
                '~(Mundstück)|(Drip Tip)|(Drip Cap)~'                => 'Zubehör > Mundstücke & Schutz',
                '~(Glastank)|(Hollowed Out Tank)|(Tankschutz)~'      => 'Zubehör > Glastanks',
                '~[Dd]ichtung~'                                      => 'Zubehör > Dichtungen',
                '~([Kk]abel)|([Ss]tecker)~'                          => 'Zubehör > Kabel & Stecker',
                '~(Abdeckung)|(Vitrine)|(Vape Bands)~'               => 'Zubehör > Accessoires',
                '~[Mm]agnet~'                                        => 'Zubehör > sonstiges',
            ]
        ],
    ],

    'innocigs_brands'        => ['SC', 'Steamax', 'InnoCigs', 'Innocigs'],
    'innocigs_manufacturers' => ['SC', 'Steamax', 'InnoCigs', 'Innocigs', 'Akkus'],

    'articles' => 'This key is reserverd for PropertyMapperFactory',
    'log'      => [
        'brand',
        'supplier',
        'option',
        'name',
        'replacement',
        'category',
    ]
];