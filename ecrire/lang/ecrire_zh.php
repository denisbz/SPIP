<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://www.spip.net/trad-lang/
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

// A
'activer_plugin' => 'Activer le plugin', # NEW
'affichage' => 'Affichage', # NEW
'aide_non_disponible' => '这部分在线帮助尚无中文版本.',
'auteur' => 'Auteur :', # NEW
'avis_acces_interdit' => '限制访问.',
'avis_article_modifie' => '警告, @nom_auteur_modif@在@date_diff@分钟前修改过此文',
'avis_aucun_resultat' => '没有结果.',
'avis_base_inaccessible' => 'Impossible de se connecter à la base de données @base@.', # NEW
'avis_chemin_invalide_1' => '您所选路径',
'avis_chemin_invalide_2' => '无效. 请返回前页校验提供的信息.',
'avis_connexion_echec_1' => '连接MYSQL服务器失败.', # MODIF
'avis_connexion_echec_2' => '请返回前页校验提供的信息.',
'avis_connexion_echec_3' => '<b>N.B.</b> 在许多服务器上运行时, 使用前您必须<b>请求</b>激活访问MYSQL数据库的权限.如果您无法连接, 请首先检验您是否有效激活该权限.', # MODIF
'avis_connexion_ldap_echec_1' => '连接LDAP服务器失败.',
'avis_connexion_ldap_echec_2' => '返回前页校验您所提供的信息.',
'avis_connexion_ldap_echec_3' => '请勿使用LDAP支持导入用户.',
'avis_conseil_selection_mot_cle' => '<b>重要组:</b> 强烈建议从组中选择一个关键词.',
'avis_deplacement_rubrique' => '注意! 该专栏包含 @contient_breves@ 简要@scb@: 如果您要移动它,请选择该确认框.',
'avis_destinataire_obligatoire' => '发送消息前请选择接收者.',
'avis_doublon_mot_cle' => 'Un mot existe deja avec ce titre. Êtes vous sûr de vouloir créer le même ?', # NEW
'avis_erreur_connexion_mysql' => 'SQL连接失败',
'avis_erreur_version_archive' => '<b>注意! 文件 @archive@ 与您已安装的SPIP版本不一致
    </b> 您面临一个大问题: 极有可能破坏数据库,
    引起站点的各种故障. 请勿提交您的导入请求.
    <p>
    详细信息,请看 <a href="@spipnet@">
                                 SPIP 文档</a>.', # MODIF
'avis_espace_interdit' => '<b>禁止区</b><p>SPIP已安装.', # MODIF
'avis_lecture_noms_bases_1' => '安装程序无法读取已安装的数据库的名称.',
'avis_lecture_noms_bases_2' => '要么是数据库不可用,要么数据库的允许特性因安全原因被禁止
(这是多主机的的一个例子).',
'avis_lecture_noms_bases_3' => '第二种情况为使用您的用户名登录后的数据库是可用的:',
'avis_non_acces_message' => '您无权查看此消息.',
'avis_non_acces_page' => '您无权查看此页.',
'avis_operation_echec' => '操作失败.',
'avis_operation_impossible' => 'Opération impossible', # NEW
'avis_probleme_archive' => '读取文件@archive@失败 ',
'avis_site_introuvable' => '站点未找到',
'avis_site_syndique_probleme' => '警告: 联合站点遇到问题; 目前系统临时中断. 请确认站点的联合文件地址(<b>@url_syndic@</b>), 重新尝试执行信息恢复.', # MODIF
'avis_sites_probleme_syndication' => '这些站点遇到联合问题',
'avis_sites_syndiques_probleme' => '这些联合站点出现问题',
'avis_suppression_base' => '注意, 数据删除不可挽回',
'avis_version_mysql' => 'SQL (@version_mysql@) 版本不允许数据库表格的自动修复.',

// B
'bouton_acces_ldap' => '添加LDAP访问 >>', # MODIF
'bouton_ajouter' => '添加',
'bouton_ajouter_participant' => '添加参与者:',
'bouton_annonce' => '声明',
'bouton_annuler' => 'Annuler', # NEW
'bouton_checkbox_envoi_message' => '可以发消息',
'bouton_checkbox_indiquer_site' => '您必须输入站点的名字',
'bouton_checkbox_qui_attribue_mot_cle_administrateurs' => '站点管理员',
'bouton_checkbox_qui_attribue_mot_cle_redacteurs' => '编辑者',
'bouton_checkbox_qui_attribue_mot_cle_visiteurs' => '在论坛上发表消息的站点访问者.',
'bouton_checkbox_signature_unique_email' => '一个邮件地址只能有一个签名',
'bouton_checkbox_signature_unique_site' => '一个站点只能有一个签名',
'bouton_demande_publication' => '请求发表文章',
'bouton_desactive_tout' => 'Tout désactiver', # NEW
'bouton_desinstaller' => 'Désinstaller', # NEW
'bouton_effacer_index' => '删除索引',
'bouton_effacer_statistiques' => 'Effacer les statistiques', # NEW
'bouton_effacer_tout' => '删除所有',
'bouton_envoi_message_02' => '发消息',
'bouton_envoyer_message' => '最后消息:发送',
'bouton_fermer' => 'Fermer', # NEW
'bouton_forum_petition' => '论坛和请求', # MODIF
'bouton_mettre_a_jour_base' => 'Mettre à jour la base de données', # NEW
'bouton_modifier' => '修改',
'bouton_pense_bete' => '个人备注',
'bouton_radio_activer_messagerie' => '激活内部消息',
'bouton_radio_activer_messagerie_interne' => '激活内部消息',
'bouton_radio_activer_petition' => '激活请求',
'bouton_radio_afficher' => '显示',
'bouton_radio_apparaitre_liste_redacteurs_connectes' => '显示在已连接的编辑者列表中',
'bouton_radio_articles_futurs' => '只为未来的文章 (数据库无动作).',
'bouton_radio_articles_tous' => '为所有文章.',
'bouton_radio_articles_tous_sauf_forum_desactive' => '为所有文章,除了那些论坛尚未激活的.',
'bouton_radio_desactiver_messagerie' => '停用消息',
'bouton_radio_enregistrement_obligatoire' => '必须注册 
(在能发表出版物前
用户必须提供电子邮件订阅).',
'bouton_radio_envoi_annonces_adresse' => '发送声明给下列地址:',
'bouton_radio_envoi_liste_nouveautes' => '发送最近新闻列表',
'bouton_radio_moderation_priori' => '预存 (
 出版物只能管理员确认
 才能显示出来).',
'bouton_radio_modere_abonnement' => '通过订阅预存', # MODIF
'bouton_radio_modere_posteriori' => '预存后', # MODIF
'bouton_radio_modere_priori' => '预存前', # MODIF
'bouton_radio_non_apparaitre_liste_redacteurs_connectes' => '不要出现在连接编辑者列表中',
'bouton_radio_non_envoi_annonces_editoriales' => '不发送任何编辑的声明',
'bouton_radio_non_syndication' => '没有联合',
'bouton_radio_pas_petition' => '没有请求',
'bouton_radio_petition_activee' => '激活请求',
'bouton_radio_publication_immediate' => '直接消息出版物
 (投稿发送后可显示, 管理员可以
 删除它们).',
'bouton_radio_supprimer_petition' => '删除请求',
'bouton_radio_syndication' => '联合:',
'bouton_redirection' => '重定向',
'bouton_relancer_installation' => '重新安装',
'bouton_suivant' => '下一步',
'bouton_tenter_recuperation' => '试图修复',
'bouton_test_proxy' => '测试代理',
'bouton_vider_cache' => '清空缓存',
'bouton_voir_message' => '确认前预览',

// C
'cache_mode_compresse' => '缓存中的文件是以压缩方式存储。',
'cache_mode_non_compresse' => '缓存中的文件是以非压缩方式存储。',
'cache_modifiable_webmestre' => '这些参数可以被管理员修改。', # MODIF
'calendrier_synchro' => '如果您使用的日历软件与<b>iCal</b>兼容, 您可以同步站点信息.',
'config_activer_champs' => 'Activer les champs suivants', # NEW
'config_choix_base_sup' => 'indiquer une base sur ce serveur', # NEW
'config_erreur_base_sup' => 'SPIP n\'a pas accès à la liste des bases accessibles', # NEW
'config_info_base_sup' => 'Si vous avez d\'autres bases de données à interroger à travers SPIP, avec son serveur SQL ou avec un autre, le formulaire ci-dessous, vous permet de les déclarer. Si vous laissez certains champs vides, les identifiants de connexion à la base principale seront utilisés.', # NEW
'config_info_base_sup_disponibles' => 'Bases supplémentaires déjà interrogeables:', # NEW
'config_info_enregistree' => 'La nouvelle configuration a été enregistrée', # NEW
'config_info_logos' => 'Chaque élément du site peut avoir un logo, ainsi qu\'un « logo de survol »', # NEW
'config_info_logos_utiliser' => 'Utiliser les logos', # NEW
'config_info_logos_utiliser_non' => 'Ne pas utiliser les logos', # NEW
'config_info_logos_utiliser_survol' => 'Utiliser les logos de survol', # NEW
'config_info_logos_utiliser_survol_non' => 'Ne pas utiliser les logos de survol', # NEW
'config_info_redirection' => 'En activant cette option, vous pourrez créer des articles virtuels, simples références d\'articles publiés sur d\'autres sites ou hors de SPIP.', # NEW
'config_redirection' => 'Articles virtuels', # NEW
'config_titre_base_sup' => 'Déclaration d\'une base supplémentaire', # NEW
'config_titre_base_sup_choix' => 'Choisissez une base supplémentaire', # NEW
'connexion_ldap' => 'Connexion :', # NEW
'copier_en_local' => 'Copier en local', # NEW

// D
'date_mot_heures' => '时',
'diff_para_ajoute' => '增加的段落',
'diff_para_deplace' => '移动的段落',
'diff_para_supprime' => '删除的段落',
'diff_texte_ajoute' => '增加的文字',
'diff_texte_deplace' => '移动的文字',
'diff_texte_supprime' => '删除的文字',
'double_clic_inserer_doc' => 'Double-cliquez pour insérer ce raccourci dans le texte', # NEW

// E
'email' => '电子邮件',
'email_2' => '电子邮件:',
'en_savoir_plus' => 'En savoir plus', # NEW
'entree_adresse_annuaire' => '目录地址',
'entree_adresse_email' => '您的邮件地址',
'entree_adresse_fichier_syndication' => '联合所用的«引用»文件地址:', # MODIF
'entree_adresse_site' => '<b>站点地址</b> [必须的]',
'entree_base_donnee_1' => '数据库地址',
'entree_base_donnee_2' => '(该地址经常对应您的站点地址,有时对应 «localhost», 有时可以留空.)',
'entree_biographie' => '自我简介.',
'entree_chemin_acces' => '<b>输入</b> 路径:', # MODIF
'entree_cle_pgp' => '您的PGP钥匙',
'entree_contenu_rubrique' => '(专栏内容简介.)',
'entree_description_site' => '站点描述',
'entree_identifiants_connexion' => '您的连接标识符...',
'entree_informations_connexion_ldap' => '请在表单中填入LDAP连接信息. 所有信息应该由系统或网络管理员提供.',
'entree_infos_perso' => '您是谁?',
'entree_interieur_rubrique' => '在专栏内部:',
'entree_liens_sites' => '<b>超链接</b> (访问参考站点...)', # MODIF
'entree_login' => '登录用户名',
'entree_login_connexion_1' => '连接登录',
'entree_login_connexion_2' => '(有时对应您的FTP登录用户名;有时留空)',
'entree_login_ldap' => '初始LDAP登录',
'entree_mot_passe' => '密码',
'entree_mot_passe_1' => '连接密码',
'entree_mot_passe_2' => '(有时对应您的FTP登录用户名;有时留空)',
'entree_nom_fichier' => '请输入文件名 @texte_compresse@:',
'entree_nom_pseudo' => '您的名字或昵称',
'entree_nom_pseudo_1' => '(您的名字或昵称)',
'entree_nom_site' => '站点名',
'entree_nouveau_passe' => '新密码',
'entree_passe_ldap' => '密码',
'entree_port_annuaire' => '目录端口号',
'entree_signature' => '签名',
'entree_titre_obligatoire' => '<b>标题</b> [必需的]<br />', # MODIF
'entree_url' => '站点连接',
'erreur_connect_deja_existant' => 'Un serveur existe déjà avec ce nom', # NEW
'erreur_nom_connect_incorrect' => 'Ce nom de serveur n\'est pas autorisé', # NEW
'erreur_plugin_desinstalation_echouee' => 'La désinstallation du plugin a echoué. Vous pouvez néanmoins le desactiver.', # NEW
'erreur_plugin_fichier_absent' => 'Fichier absent', # NEW
'erreur_plugin_fichier_def_absent' => 'Fichier de définition absent', # NEW
'erreur_plugin_nom_fonction_interdit' => 'Nom de fonction interdit', # NEW
'erreur_plugin_nom_manquant' => 'Nom du plugin manquant', # NEW
'erreur_plugin_prefix_manquant' => 'Espace de nommage du plugin non défini', # NEW
'erreur_plugin_tag_plugin_absent' => '&lt;plugin&gt; manquant dans le fichier de définition', # NEW
'erreur_plugin_version_manquant' => 'Version du plugin manquante', # NEW

// F
'forum_info_original' => 'original', # NEW

// H
'htaccess_a_simuler' => 'Avertissement: la configuration de votre serveur HTTP ne tient pas compte des fichiers @htaccess@. Pour pouvoir assurer une bonne sécurité, il faut que vous modifiiez cette configuration sur ce point, ou bien que les constantes @constantes@ (définissables dans le fichier mes_options.php) aient comme valeur des répertoires en dehors de @document_root@.', # NEW
'htaccess_inoperant' => 'htaccess inopérant', # NEW

// I
'ical_info1' => '该页面提供了几种与本站点保持联系的方法.',
'ical_info2' => '要得到更多的信息, 请访问 <a href="@spipnet@">SPIP 文档</a>.', # MODIF
'ical_info_calendrier' => '在您的配置中有两个日历. 第一个是站点地图,它显示所有已发布的文章. 第二个包含了可编辑的声明,作为您最后的私有消息: 由于您可以随时通过更新密码来更改您的个人钥匙,它总是为您保留的.',
'ical_methode_http' => '下载',
'ical_methode_webcal' => '同步 (webcal://)',
'ical_texte_js' => '一行javascript语句允许在任何您参与的站点显示您在本站最新发表的文章.',
'ical_texte_prive' => '该日历严格限于个人使用, 提醒您在该站点上的个人活动 (任务,个人约会,提交的文章和简要...).',
'ical_texte_public' => '该日历允许您追踪站点的公共活动 (发布的文章和简要).',
'ical_texte_rss' => '您可以用任何XML/RSS(Rich Site Summary)阅读器联合站点的最近新闻以便阅读. XML/RSS同样是允许从其它SPIP站点读取/交换最近新闻的格式.',
'ical_titre_js' => 'Javascript',
'ical_titre_mailing' => '邮件列表',
'ical_titre_rss' => '«引用»文件', # MODIF
'icone_accueil' => 'Accueil', # NEW
'icone_activer_cookie' => '激活相应cookie',
'icone_activite' => 'Activité', # NEW
'icone_admin_plugin' => 'Gestion des plugins', # NEW
'icone_administration' => 'Maintenance', # NEW
'icone_afficher_auteurs' => '显示作者',
'icone_afficher_visiteurs' => '显示访问者',
'icone_arret_discussion' => '停止参与该讨论',
'icone_calendrier' => '日历',
'icone_configuration' => 'Configuration', # NEW
'icone_creation_groupe_mots' => '新建一个关键词组',
'icone_creation_mots_cles' => '新建一个关键词',
'icone_creer_auteur' => '新建一个作者并与该文章关联',
'icone_creer_mot_cle' => '新建一个关键词并与该文章关联',
'icone_creer_mot_cle_rubrique' => 'Créer un nouveau mot-clé et le lier à cette rubrique', # NEW
'icone_creer_mot_cle_site' => 'Créer un nouveau mot-clé et le lier à ce site', # NEW
'icone_creer_rubrique_2' => '新建专栏',
'icone_edition' => 'Édition', # NEW
'icone_envoyer_message' => '发送这个消息',
'icone_evolution_visites' => '访问进展<br />@visites@个访问', # MODIF
'icone_ma_langue' => 'Ma langue', # NEW
'icone_mes_infos' => 'Mes informations', # NEW
'icone_mes_preferences' => 'Mes préférences', # NEW
'icone_modif_groupe_mots' => '修改该词组',
'icone_modifier_article' => '修改文章',
'icone_modifier_message' => '修改消息',
'icone_modifier_mot' => 'Modifier ce mot-clé', # NEW
'icone_modifier_rubrique' => '修改此栏',
'icone_modifier_site' => '修改站点',
'icone_poster_message' => '发表消息',
'icone_publication' => 'Publication', # NEW
'icone_referencer_nouveau_site' => '引用一个新站点',
'icone_relancer_signataire' => 'Relancer le signataire', # NEW
'icone_retour' => '返回',
'icone_retour_article' => '返回文章',
'icone_squelette' => 'Squelettes', # NEW
'icone_suivi_forum' => '跟踪公共论坛: @nb_forums@ 出版物',
'icone_suivi_publication' => 'Suivi de la publication', # NEW
'icone_supprimer_cookie' => '删除cookie',
'icone_supprimer_groupe_mots' => '删除组',
'icone_supprimer_rubrique' => '删除此栏',
'icone_supprimer_signature' => '删除签名',
'icone_valider_signature' => '使签名有效',
'icone_voir_sites_references' => '查看参考站点',
'icone_voir_tous_mots_cles' => '查看所有关键词',
'image_administrer_rubrique' => '您可以管理该栏',
'info_1_article' => '1篇文章',
'info_1_article_syndique' => '1 article syndiqué', # NEW
'info_1_auteur' => '1 auteur', # NEW
'info_1_message' => '1 message', # NEW
'info_1_mot_cle' => '1 mot-clé', # NEW
'info_1_rubrique' => '1 rubrique', # NEW
'info_1_site' => '1个站点',
'info_1_visiteur' => '1 visiteur', # NEW
'info_activer_cookie' => '您可以激活<b>相应的cookie</b>,以便让您轻松转换公共站点为私私人站点.',
'info_activer_forum_public' => '<i>若要激活公共论坛, 请选择默认模式:</i>', # MODIF
'info_admin_etre_webmestre' => 'Me donner les droits de webmestre', # NEW
'info_admin_gere_rubriques' => '该管理员管理以下专栏:',
'info_admin_gere_toutes_rubriques' => '该管理员管理 <b>所有专栏</b>.',
'info_admin_je_suis_webmestre' => 'Je suis <b>webmestre</b>', # NEW
'info_admin_statuer_webmestre' => 'Donner à cet administrateur les droits de webmestre', # NEW
'info_admin_webmestre' => 'Cet administrateur est <b>webmestre</b>', # NEW
'info_administrateur' => '管理员',
'info_administrateur_1' => '管理员',
'info_administrateur_2' => '站点 (<i>谨慎使用</i>)',
'info_administrateur_site_01' => '如果您是站点管理员,请',
'info_administrateur_site_02' => '点击链接',
'info_administrateurs' => '管理员',
'info_administrer_rubrique' => '您可以管理该栏',
'info_adresse' => '给地址:',
'info_adresse_email' => '电子邮件地址:',
'info_adresse_url' => '您的公众站点URL地址',
'info_afficher_visites' => '显示访问者:',
'info_affichier_visites_articles_plus_visites' => '显示<b>从开始访问最流行文章的</b>访问者:',
'info_aide_en_ligne' => 'SPIP在线帮助',
'info_ajout_image' => '当您添加图像作为文章的附加文档,  SPIP 能根据插入的图片自动创建缩略图.
这将允许, 例如, 自动创建
  画廊或相册.',
'info_ajout_participant' => '下列参考者已经加入:',
'info_ajouter_rubrique' => '加入其它专栏进行管理:',
'info_annonce_nouveautes' => '最近的新闻声明',
'info_anterieur' => '返回',
'info_appliquer_choix_moderation' => '应用缓冲选择:',
'info_article' => '文章',
'info_article_2' => '文章',
'info_article_a_paraitre' => '过期文章发表',
'info_articles_02' => '文章',
'info_articles_2' => '文章',
'info_articles_auteur' => '该作者的文章',
'info_articles_lies_mot' => '与关键词关联的文章',
'info_articles_miens' => 'Mes articles', # NEW
'info_articles_tous' => 'Tous les articles', # NEW
'info_articles_trouves' => '找到的文章',
'info_articles_trouves_dans_texte' => '找到的文章 (文本)',
'info_attente_validation' => '您的文章正在等候确认中',
'info_aucun_article' => 'Aucun article', # NEW
'info_aucun_article_syndique' => 'Aucun article syndiqué', # NEW
'info_aucun_auteur' => 'Aucun auteur', # NEW
'info_aucun_message' => 'Aucun message', # NEW
'info_aucun_mot_cle' => 'Aucun mot-clé', # NEW
'info_aucun_rubrique' => 'Aucune rubrique', # NEW
'info_aucun_site' => 'Aucun site', # NEW
'info_aucun_visiteur' => 'Aucun visiteur', # NEW
'info_aujourdhui' => '今天:',
'info_auteur_message' => '消息发送者:',
'info_auteurs' => '作者',
'info_auteurs_par_tri' => '作者 @partri@',
'info_auteurs_trouves' => '找到的作者',
'info_authentification_externe' => '外部验证',
'info_avertissement' => '消息',
'info_barre_outils' => 'avec sa barre d\'outils ?', # NEW
'info_base_installee' => '您的数据库已经安装.',
'info_bloquer' => 'bloquer', # NEW
'info_changer_nom_groupe' => '改变组的名字:',
'info_chapeau' => '前言',
'info_chapeau_2' => '前言:',
'info_chemin_acces_1' => '选项: <b>目录的访问路径</b>', # MODIF
'info_chemin_acces_2' => '从现在开始您必须配置目录的访问路径. 这是存在目录中的用户说明文件精要.',
'info_chemin_acces_annuaire' => '选项: <b>目录的访问路径</b>', # MODIF
'info_choix_base' => '第三步:',
'info_classement_1' => '<sup>st</sup> 出了 @liste@',
'info_classement_2' => '<sup>th</sup> 出了 @liste@',
'info_code_acces' => '不要忘记你的访问码!',
'info_comment_lire_tableau' => '如何读图',
'info_compatibilite_html' => 'Norme HTML à suivre', # NEW
'info_compresseur_gzip' => '<b>N. B. :</b> Il est recommandé de vérifier au préalable si l\'hébergeur compresse déjà systématiquement les scripts php ; pour cela, vous pouvez par exemple utiliser le service suivant : @testgzip@', # NEW
'info_compresseur_texte' => 'Si votre serveur ne comprime pas automatiquement les pages html pour les envoyer aux internautes, vous pouvez essayer de forcer cette compression pour diminuer le poids des pages téléchargées. <b>Attention</b> : cela peut ralentir considerablement certains serveurs.', # NEW
'info_compresseur_titre' => 'Optimisations et compression', # NEW
'info_config_forums_prive' => 'Dans l’espace privé du site, vous pouvez activer plusieurs types de forums :', # NEW
'info_config_forums_prive_admin' => 'Un forum réservé aux administrateurs du site :', # NEW
'info_config_forums_prive_global' => 'Un forum global, ouvert à tous les rédacteurs :', # NEW
'info_config_forums_prive_objets' => 'Un forum sous chaque article, brève, site référencé, etc. :', # NEW
'info_config_suivi' => '如果地址对应邮件列表, 你可以简要说明以下地址(从这儿能注册参与). 地址可以是URL (例如通过页面注册), 或通过电子邮件给一个特殊的标题(例如: <tt>@adresse_suivi@?subject=subscribe</tt>):',
'info_config_suivi_explication' => '你可以订阅站点的邮件列表. 随后你将接到自动邮件,关于文章和新闻的声明将提交发表.',
'info_confirmer_passe' => '确认新密码:',
'info_conflit_edition_avis_non_sauvegarde' => 'Attention, les champs suivants ont été modifiés par ailleurs. Vos modifications sur ces champs n\'ont donc pas été enregistrées.', # NEW
'info_conflit_edition_differences' => 'Différences :', # NEW
'info_conflit_edition_version_enregistree' => 'La version enregistrée :', # NEW
'info_conflit_edition_votre_version' => 'Votre version :', # NEW
'info_connexion_base' => '第二步: <b>试图连接到数据库</b>', # MODIF
'info_connexion_base_donnee' => 'Connexion à votre base de données', # NEW
'info_connexion_ldap_ok' => '<MODIF><b>你的 LDAP 连接成功.</b><p> 你可进行下一步操作.', # MODIF
'info_connexion_mysql' => '第一步: <b>你的 SQL 连接</b>', # MODIF
'info_connexion_ok' => '连接成功.',
'info_contact' => '联系',
'info_contenu_articles' => '文章内容',
'info_contributions' => 'Contributions', # NEW
'info_creation_mots_cles' => '在这里新建和配置站点关键词',
'info_creation_paragraphe' => '(新建段落, 只需空一行.)',
'info_creation_rubrique' => '在能够发表文章之前,<br /> 您必须创建至少一个专栏.<br />', # MODIF
'info_creation_tables' => '第四步: <b>创建数据库表</b>', # MODIF
'info_creer_base' => '<b>新建</b> 数据库:', # MODIF
'info_dans_groupe' => '组中',
'info_dans_rubrique' => '所属专栏:',
'info_date_publication_anterieure' => '更早出版的日期:', # MODIF
'info_date_referencement' => '参考站点日期:',
'info_delet_mots_cles' => '你被请求删除关键
<b>@titre_mot@</b> (@type_mot@). 关键词被连到
<b>@texte_lie@</b>你必须确认决定:', # MODIF
'info_derniere_etape' => '最后一步: <b>完成了!', # MODIF
'info_derniere_syndication' => '站点的最近联合己移出',
'info_derniers_articles_publies' => '你最近出版的文章',
'info_desactiver_forum_public' => '停用公共论坛.
 公共论坛只能通过一篇一篇文章的访问;
它们的专栏和简要等将被禁止.',
'info_desactiver_messagerie_personnelle' => '你可激活或使站点个人消息不可用.',
'info_descriptif' => '描述:',
'info_desinstaller_plugin' => 'supprime les données et désactive le plugin', # NEW
'info_discussion_cours' => '讨论进展中',
'info_ecrire_article' => '在能够发表文章之前,您必须建立至少一个专栏.',
'info_email_envoi' => '发送者电子邮件地址 (可选)',
'info_email_envoi_txt' => '输入发送者电子邮件地址,发送电子邮件将用这个地址, 接收者的地址将做为发送者的地址 :',
'info_email_webmestre' => 'Web站点管理员的电子邮件地址 (可选)',
'info_entrer_code_alphabet' => '输入要用的字符集:',
'info_envoi_email_automatique' => '自动邮寄',
'info_envoi_forum' => '发送论坛给文章作者 ',
'info_envoyer_maintenant' => '现在发送',
'info_etape_suivante' => '到下一步',
'info_etape_suivante_1' => '你可移动到下一步.',
'info_etape_suivante_2' => '你可移动到下一步.',
'info_exceptions_proxy' => 'Exceptions pour le proxy', # NEW
'info_exportation_base' => '导出数据库到 @archive@',
'info_facilite_suivi_activite' => '为减轻站点编辑的跟踪;
  活动, SPIP 通过电子邮件发送给编辑的邮件列表作为实例,
  公共请求和文章
  确认的声明.',
'info_fichiers_authent' => '认证文件 ".htpasswd"',
'info_fonctionnement_forum' => '论坛操作:',
'info_forum_administrateur' => '管理者论坛',
'info_forum_interne' => '内部论坛',
'info_forum_ouvert' => '站点的私有区, 论坛对
  所有注册用户开放. 下面, 你可以激活一个为管理员
  保留的论坛.',
'info_forum_statistiques' => '访问统计',
'info_forums_abo_invites' => '您的网站包含要求注册的公共论坛；所以公共网站的访客将被要求注册。',
'info_gauche_admin_effacer' => '<MODIF><b>只有管理员才有权访问该页.</b><p> 它提供访问不同的技术维护任务. 其中有些需要特殊认证，必须通过FTP访问站点.', # MODIF
'info_gauche_admin_tech' => '<b>只有管理者才有权访问这页.</b><p> 它提供多种多种
维护任务. 它们有一些需更高的认证
(通过FTP访问站点).', # MODIF
'info_gauche_admin_vider' => '<b>只有管理者才有权访问这页.</b><p> 它提供多种维护任务
. 它们有一些需更高的认证
(通过FTP访问站点).', # MODIF
'info_gauche_auteurs' => '<MODIF>你将找到站点所有的作者.
 每一个的状态用路标的颜色标识(作者 = 绿色; 管理员 = 黄色).',
'info_gauche_auteurs_exterieurs' => '外部作者用蓝色图标标识, 不能访问站点; 通过垃圾箱删除作者.',
'info_gauche_messagerie' => '消息允许你在作者中交换消息, 为保护备忘录(给个人用的) 或在主页私有区上显示声明(如果你是管理者).',
'info_gauche_numero_auteur' => '作者号:',
'info_gauche_statistiques_referers' => '<MODIF>页面显示 <i>引用</i>列表, 例如. 包含你站点的链接, 只有今天: 列表每24小时都要更新.',
'info_gauche_suivi_forum' => ' <i>论坛跟踪</i> 页是你站点的一个管理工具 (不是讨论或编辑区). 它显示这篇文章的所有论坛出版物并允许你管理这些出版物.', # MODIF
'info_gauche_suivi_forum_2' => ' <i>论坛跟踪</i> 页是你站点的一个管理工具(不是讨论或编辑). 它显示这篇文章的所有论坛出版物并允许你管理这些出版物.', # MODIF
'info_gauche_visiteurs_enregistres' => '在这儿你将找到在站点公共区
 注册的访问者(订阅论坛).',
'info_generation_miniatures_images' => '产生像册',
'info_gerer_trad' => '管理翻译连接?',
'info_gerer_trad_objets' => '@objets@ : gérer les liens de traduction', # NEW
'info_groupe_important' => '重要组',
'info_hebergeur_desactiver_envoi_email' => '一些主机禁止自动邮件发送
 . 这种情况下SPIP的
  以下特性不能用.',
'info_hier' => '昨天:',
'info_historique' => '修订：',
'info_historique_activer' => '使用修订跟踪功能。',
'info_historique_affiche' => '显示这一版本。',
'info_historique_comparaison' => '比较',
'info_historique_desactiver' => '不使用修订跟踪功能',
'info_historique_lien' => '显示修订历史',
'info_historique_texte' => '修订跟踪功能可以保存对一篇文章内容所做的所有的修改，并且显示前后不同版本之间的区别。',
'info_historique_titre' => '修订跟踪',
'info_identification_publique' => '你的公开标识...',
'info_image_process' => '点击相关图片选取最佳的标志制作方法.',
'info_image_process2' => '<b>注意</b> <i>如果没有任何图片显示，那么储存您的网站的服务器不支持该工具。如果您希望使用这些功能，请联系您的服务器的技术支持，请他们安装《GD》或者《Imagick》扩展。</i>',
'info_images_auto' => 'Images calculées automatiquement', # NEW
'info_informations_personnelles' => '第五步: <b>个人信息</b>', # MODIF
'info_inscription_automatique' => '新编辑自动注册系统',
'info_jeu_caractere' => '站点的字符集',
'info_jours' => '天',
'info_laisser_champs_vides' => '文本框留空)',
'info_langues' => '站点语言',
'info_ldap_ok' => 'LDAP 验证已安装.',
'info_lien_hypertexte' => '超链接:',
'info_liens_syndiques_1' => '联合连接',
'info_liens_syndiques_2' => '未确认.',
'info_liens_syndiques_3' => '论坛',
'info_liens_syndiques_4' => '是',
'info_liens_syndiques_5' => '论坛',
'info_liens_syndiques_6' => '是',
'info_liens_syndiques_7' => '未确认.',
'info_liste_redacteurs_connectes' => '列出连接的编辑者',
'info_login_existant' => '这个登录名已经存在.',
'info_login_trop_court' => '登录名太短.',
'info_logos' => 'Les logos', # NEW
'info_maximum' => '最大:',
'info_meme_rubrique' => '在同一栏目',
'info_message' => '消息来自',
'info_message_efface' => '删除的消息',
'info_message_en_redaction' => '你的进展中的消息',
'info_message_technique' => '技术消息:',
'info_messagerie_interne' => '内部消息',
'info_mise_a_niveau_base' => 'SQL 数据库升级',
'info_mise_a_niveau_base_2' => '{{警告!}} 你已经安装的SPIP的
  版本 {老于} 以前安装的
  : 你的数据库有丢失的危险
  并且再也不能正常工作.<br />{{重新安装
  SPIP 文件.}}', # MODIF
'info_mode_fonctionnement_defaut_forum_public' => '公众论坛的缺省模式',
'info_modification_enregistree' => 'Votre modification a été enregistrée', # NEW
'info_modifier_auteur' => 'Modifier l\'auteur :', # NEW
'info_modifier_mot' => 'Modifier le mot-clé :', # NEW
'info_modifier_rubrique' => '修改专栏:',
'info_modifier_titre' => '修改: @titre@',
'info_mon_site_spip' => '我的 SPIP 站点',
'info_mot_sans_groupe' => '(不在组中的关键词...)',
'info_moteur_recherche' => '集成的搜索引擎',
'info_mots_cles' => '关键词',
'info_mots_cles_association' => '组中的关键词能被关联:',
'info_moyenne' => '平均:',
'info_multi_articles' => '使文章的语言菜单可用?',
'info_multi_cet_article' => '文章的语言:',
'info_multi_langues_choisies' => '请在站点中选择以下语言使它们对编辑者可用.
 你的站点已经用了如下语言(在顶端列表),它们不能设为未激活.',
'info_multi_objets' => '@objets@ : activer le menu de langue', # NEW
'info_multi_rubriques' => '激活专栏中的语言菜单?',
'info_multi_secteurs' => '... 只为站点根目录下的专栏?',
'info_nb_articles' => '@nb@ articles', # NEW
'info_nb_articles_syndiques' => '@nb@ articles syndiqués', # NEW
'info_nb_auteurs' => '@nb@ auteurs', # NEW
'info_nb_messages' => '@nb@ messages', # NEW
'info_nb_mots_cles' => '@nb@ mots-clés', # NEW
'info_nb_rubriques' => '@nb@ rubriques', # NEW
'info_nb_sites' => '@nb@ sites', # NEW
'info_nb_visiteurs' => '@nb@ visiteurs', # NEW
'info_nom' => '名字',
'info_nom_destinataire' => '接收者名字',
'info_nom_site' => '你的站点名',
'info_nom_site_2' => '<b>站点名</b> [必须]',
'info_nombre_articles' => '@nb_articles@ 文章,',
'info_nombre_partcipants' => '讨论的参考者:',
'info_nombre_rubriques' => '专栏@nb_rubriques@,',
'info_nombre_sites' => '@nb_sites@ 站点,',
'info_non_deplacer' => '不要移动...',
'info_non_envoi_annonce_dernieres_nouveautes' => 'SPIP 能定期主动发送站点的最新新闻声明.
  (最新发表的文章和新闻).',
'info_non_envoi_liste_nouveautes' => '不能发送新新闻列表',
'info_non_modifiable' => '不能修改',
'info_non_suppression_mot_cle' => '我不想删除关键词.',
'info_note_numero' => 'Note @numero@', # NEW
'info_notes' => '脚注',
'info_nouveaux_message' => '新消息',
'info_nouvel_article' => '新文章',
'info_nouvelle_traduction' => '新译文:',
'info_numero_article' => '文章号:',
'info_obligatoire_02' => '[必须的]',
'info_option_accepter_visiteurs' => '允许公共网站访问者注册。',
'info_option_email' => '当一个站点访问者在论坛发表一个关联文章的消息
  , 文章的作者能被电子邮件通知
  . 你愿意用这个选项吗?', # MODIF
'info_option_faire_suivre' => '转寄论坛消息给作者',
'info_option_ne_pas_accepter_visiteurs' => '拒绝公共网站访问者注册。',
'info_option_ne_pas_faire_suivre' => '不要转寄论坛消息',
'info_options_avancees' => '高级选项',
'info_ortho_activer' => '使用拼写检查功能',
'info_ortho_desactiver' => '不使用拼写检查功能',
'info_ou' => '或...',
'info_oui_suppression_mot_cle' => '我要永久删除关键词.',
'info_page_interdite' => '禁止页',
'info_par_nom' => 'par nom', # NEW
'info_par_nombre_article' => '(按文章数)', # MODIF
'info_par_statut' => 'par statut', # NEW
'info_par_tri' => '\'(par @tri@)\'', # NEW
'info_pas_de_forum' => '没有论坛',
'info_passe_trop_court' => '密码过短.',
'info_passes_identiques' => '两个密码不一致.',
'info_pense_bete_ancien' => '你的旧备忘', # MODIF
'info_plus_cinq_car' => '多于5 字符',
'info_plus_cinq_car_2' => '(多于 5 字符)',
'info_plus_trois_car' => '(多于 3 字符)',
'info_popularite' => '流行: @popularite@; 访问: @visites@',
'info_popularite_2' => '站点流行:',
'info_popularite_3' => '流行: @popularite@; 访问: @visites@',
'info_popularite_4' => '流行: @popularite@; 访问: @visites@',
'info_post_scriptum' => '后记',
'info_post_scriptum_2' => '后记:',
'info_pour' => '为',
'info_preview_admin' => '只有管理员可以预览网站',
'info_preview_comite' => '所有的编辑均可预览网站',
'info_preview_desactive' => '完全关闭预览功能',
'info_preview_texte' => '可以预览整个网站，就像所有的文章和短消息（至少有 « 建议发表 »资格）都被发表了一样。向管理员，编辑开放这一功能，还是不向任何人开放？',
'info_previsions' => 'prévisions :', # NEW
'info_principaux_correspondants' => '你主要的通讯者',
'info_procedez_par_etape' => '请一步步进行下去',
'info_procedure_maj_version' => '升级过程应该适应
 SPIP的新版本的数据库运行.',
'info_proxy_ok' => 'Test du proxy réussi.', # NEW
'info_ps' => 'P.S.', # MODIF
'info_publier' => 'publier', # NEW
'info_publies' => '你的文章在线出版',
'info_question_accepter_visiteurs' => '如果您的网站骨架设定访问者可以从公共网站注册，而不用到私人空间，请激活如下功能:',
'info_question_activer_compactage_css' => 'Souhaitez-vous activer le compactage des feuilles de style (CSS) ?', # NEW
'info_question_activer_compactage_js' => 'Souhaitez-vous activer le compactage des scripts (javascript) ?', # NEW
'info_question_activer_compresseur' => 'Voulez-vous activer la compression du flux HTTP ?', # NEW
'info_question_gerer_statistiques' => '你的站点管理访问者统计吗?',
'info_question_inscription_nouveaux_redacteurs' => '你允许新编辑从公共站点注册吗?
  如果你愿意, 访问将通过自动表单注册
  , 将能访问私有区维护文章
  . <blockquote><i>光注册过程中,
  用户使用自动电子邮件提供的访问码访问私有站点.
  . 一些主机使自动发送不可用,
  这样,
  自动注册将
  不生效.', # MODIF
'info_question_mots_cles' => '你希望站点使用关键词吗?',
'info_question_proposer_site' => '谁能提出引用站点?',
'info_question_utilisation_moteur_recherche' => '你希望SPIP集成搜索引擎吗?
 (使它不可用能加速系统的性能.)',
'info_question_vignettes_referer' => 'Lorsque vous consultez les statistiques, vous pouvez visualiser des aperçus des sites d\'origine des visites', # NEW
'info_question_vignettes_referer_non' => 'Ne pas afficher les captures des sites d\'origine des visites', # NEW
'info_question_vignettes_referer_oui' => 'Afficher les captures des sites d\'origine des visites', # NEW
'info_question_visiteur_ajout_document_forum' => 'Si vous souhaitez autoriser les visiteurs à joindre des documents (images, sons...) à leurs messages de forum, indiquer ci-dessous la liste des extensions de documents autorisés pour les forums (ex: gif, jpg, png, mp3).', # NEW
'info_question_visiteur_ajout_document_forum_format' => 'Si vous souhaitez autoriser tous les types de documents considérés comme fiables par SPIP, mettre une étoile. Pour ne rien autoriser, ne rien indiquer.', # NEW
'info_qui_attribue_mot_cle' => '组中的关键词能被分配:',
'info_racine_site' => '站点根',
'info_recharger_page' => '请重新载入该页.',
'info_recherche_auteur_a_affiner' => '太多结果"@cherche_auteur@"; 请重定义搜索.',
'info_recherche_auteur_ok' => '几个编辑者找到了 "@cherche_auteur@":',
'info_recherche_auteur_zero' => '<MODIF><b> "@cherche_auteur@"没有结果.',
'info_recommencer' => '请再试.',
'info_redacteur_1' => 'Rédacteur',
'info_redacteur_2' => '有权访问私有区 (<i>推荐</i>)',
'info_redacteurs' => '编辑者',
'info_redaction_en_cours' => '在编辑中',
'info_redirection' => '重定向',
'info_referencer_doc_distant' => 'Référencer un document sur l\'internet :', # NEW
'info_refuses' => '你的文章被拒',
'info_reglage_ldap' => '选项: <b>调整 LDAP 导入</b>', # MODIF
'info_remplacer_mot' => 'Remplacer "@titre@"', # NEW
'info_renvoi_article' => '<b>重定向.</b> 引用该页的文章:', # MODIF
'info_reserve_admin' => '只有管理能改这个地址.',
'info_restreindre_rubrique' => '限制专栏管理:',
'info_resultat_recherche' => '搜索结果:',
'info_rubriques' => '专栏',
'info_rubriques_02' => '专栏',
'info_rubriques_liees_mot' => '与该关键词相关的专栏',
'info_rubriques_trouvees' => '找到的专栏',
'info_rubriques_trouvees_dans_texte' => '找到的专栏(在文章中)',
'info_sans_titre' => '无标题',
'info_selection_chemin_acces' => '从目录的访问路径<b>选择</b> :',
'info_selection_un_seul_mot_cle' => '你一次从组中可选择 <b>只有一个关键词</b> .',
'info_signatures' => '签名',
'info_site' => '站点',
'info_site_2' => '站点:',
'info_site_min' => '站点',
'info_site_propose' => '提交的站点:',
'info_site_reference_2' => '引用的站点',
'info_site_syndique' => '联合的站点...',
'info_site_valider' => '使有效的站点',
'info_site_web' => '站点:',
'info_sites' => '站点',
'info_sites_lies_mot' => '与关键词关联的参考站点',
'info_sites_proxy' => '使用代理',
'info_sites_refuses' => '丢弃的站点',
'info_sites_trouves' => '站点找到了',
'info_sites_trouves_dans_texte' => '站点找到了 (在正文)',
'info_sous_titre' => '子标题:',
'info_statut_administrateur' => '管理者',
'info_statut_auteur' => '作者状态:', # MODIF
'info_statut_auteur_a_confirmer' => 'Inscription à confirmer', # NEW
'info_statut_auteur_autre' => 'Autre statut :', # NEW
'info_statut_efface' => '删除',
'info_statut_redacteur' => '编辑者',
'info_statut_site_1' => '站点是:',
'info_statut_site_2' => '出版',
'info_statut_site_3' => '提交',
'info_statut_site_4' => '到垃圾箱',
'info_statut_utilisateurs_1' => '导入用户的缺省状态',
'info_statut_utilisateurs_2' => 'Choose the status that is attributed to the persons present in the LDAP directory when they connect for the first time. Later, you can modify this value for each author on a case by case basis.',
'info_suivi_activite' => '继续使编辑可用',
'info_supprimer_mot' => '删除小关键词',
'info_surtitre' => '顶标题:',
'info_syndication_integrale_1' => 'Votre site propose des fichiers de syndication (voir « <a href="@url@">@titre@</a> »).', # NEW
'info_syndication_integrale_2' => 'Souhaitez-vous transmettre les articles dans leur intégralité, ou ne diffuser qu\'un résumé de quelques centaines de caractères ?', # NEW
'info_table_prefix' => 'Vous pouvez modifier le préfixe du nom des tables de données (ceci est indispensable lorsque l\'on souhaite installer plusieurs sites dans la même base de données). Ce préfixe s\'écrit en lettres minuscules, non accentuées, et sans espace.', # NEW
'info_taille_maximale_images' => 'SPIP va tester la taille maximale des images qu\'il peut traiter (en millions de pixels).<br /> Les images plus grandes ne seront pas réduites.', # NEW
'info_taille_maximale_vignette' => '系统产生的小插图的最大尺寸:',
'info_terminer_installation' => '现在你可以完成标准安装过程.',
'info_texte' => '正文',
'info_texte_explicatif' => '展开正文',
'info_texte_long' => '(正文太长: 将分几部分显示,确认后能合并在一起.)',
'info_texte_message' => '你的消息正文:',
'info_texte_message_02' => '消息正文',
'info_titre' => '标题:',
'info_titre_mot_cle' => '关键词的名字和标题',
'info_total' => '所有:',
'info_tous_articles_en_redaction' => '进展中的所有文章',
'info_tous_articles_presents' => '该专栏中所有发表的文章',
'info_tous_articles_refuses' => 'Tous les articles refusés', # NEW
'info_tous_les' => '每一个:',
'info_tous_redacteurs' => '对所有编辑者的声明',
'info_tout_site' => '整个站点',
'info_tout_site2' => '该文章尚未译成中文.',
'info_tout_site3' => '文章已经译为本语言,但由参考文章带来一些变动.译文应更新.',
'info_tout_site4' => '该文章已经译为中文并更新.',
'info_tout_site5' => '源文章.',
'info_tout_site6' => '<b>注意 :</b> 这里只显示源文件.
各翻译版本已与源文件相关联,
并以不同的颜色标识当前状态 :',
'info_traductions' => 'Traductions', # NEW
'info_travail_colaboratif' => '合力工作文章',
'info_un_article' => '一个文章,',
'info_un_mot' => '一次一个关键词',
'info_un_site' => '一个站点,',
'info_une_rubrique' => '一个专栏,',
'info_une_rubrique_02' => '1个专栏',
'info_url' => 'URL:', # MODIF
'info_url_proxy' => 'URL du proxy', # NEW
'info_url_site' => '站点 URL:', # MODIF
'info_url_test_proxy' => 'URL de test', # NEW
'info_urlref' => '超链接:',
'info_utilisation_spip' => 'SPIP 准备使用...',
'info_visites_par_mois' => '每月显示:',
'info_visites_plus_populaires' => '显示 <b>最流行的文章</b> 访问者和 <b>最近发表的文章:</b>',
'info_visiteur_1' => '访问者',
'info_visiteur_2' => '公共站点',
'info_visiteurs' => '访问者',
'info_visiteurs_02' => '公众站点访问者',
'info_webmestre_forces' => 'Les webmestres sont actuellement définis dans <tt>@file_options@</tt>.', # NEW
'install_adresse_base_hebergeur' => 'Adresse de la base de données attribuée par l\'hébergeur', # NEW
'install_base_ok' => 'La base @base@ a été reconnue', # NEW
'install_connect_ok' => 'La nouvelle base a bien été déclarée sous le nom de serveur @connect@.', # NEW
'install_echec_annonce' => 'L\'installation va probablement échouer, ou aboutir à un site non fonctionnel...', # NEW
'install_extension_mbstring' => 'SPIP ne fonctionne pas avec :', # NEW
'install_extension_php_obligatoire' => 'SPIP exige l\'extension php :', # NEW
'install_login_base_hebergeur' => 'Login de connexion attribué par l\'hébergeur', # NEW
'install_nom_base_hebergeur' => 'Nom de la base attribué par l\'hébergeur :', # NEW
'install_pas_table' => 'Base actuellement sans tables', # NEW
'install_pass_base_hebergeur' => 'Mot de passe de connexion attribué par l\'hébergeur', # NEW
'install_php_version' => 'PHP version @version@ insuffisant (minimum = @minimum@)', # NEW
'install_select_langue' => '选择语言并单击 "下一步" 开始安装过程.',
'install_select_type_db' => 'Indiquer le type de base de données :', # NEW
'install_select_type_mysql' => 'MySQL', # NEW
'install_select_type_pg' => 'PostgreSQL', # NEW
'install_select_type_sqlite2' => 'SQLite 2', # NEW
'install_select_type_sqlite3' => 'SQLite 3', # NEW
'install_serveur_hebergeur' => 'Serveur de base de données attribué par l\'hébergeur', # NEW
'install_table_prefix_hebergeur' => 'Préfixe de table attribué par l\'hébergeur :', # NEW
'install_tables_base' => 'Tables de la base', # NEW
'install_types_db_connus' => 'SPIP sait utiliser <b>MySQL</b> (le plus répandu), <b>PostgreSQL</b> et <b>SQLite</b>.', # NEW
'install_types_db_connus_avertissement' => 'Attention : plusieurs plugins ne fonctionnent qu\'avec MySQL', # NEW
'intem_redacteur' => '编辑',
'intitule_licence' => 'Licence', # NEW
'item_accepter_inscriptions' => '允许注册',
'item_activer_forum_administrateur' => '激活管理者论坛',
'item_activer_messages_avertissement' => '激活警告消息',
'item_administrateur_2' => '管理者',
'item_afficher_calendrier' => '在日历中显示',
'item_ajout_mots_cles' => '认证论坛附加的关键词',
'item_autoriser_documents_joints' => '认证文章附加的文档',
'item_autoriser_documents_joints_rubriques' => '认证专栏中的文档',
'item_autoriser_selectionner_date_en_ligne' => 'Permettre de modifier la date de chaque document', # NEW
'item_autoriser_syndication_integrale' => 'Diffuser l\'intégralité des articles dans les fichiers de syndication', # NEW
'item_bloquer_liens_syndiques' => '阻止联合站点确认',
'item_choix_administrateurs' => '管理者',
'item_choix_generation_miniature' => '自动产生像片册.',
'item_choix_non_generation_miniature' => '不产生像片册.',
'item_choix_redacteurs' => '编辑者',
'item_choix_visiteurs' => '公共站点的访问者',
'item_compresseur' => 'Activer la compression', # NEW
'item_config_forums_prive_global' => 'Activer le forum des rédacteurs', # NEW
'item_config_forums_prive_objets' => 'Activer ces forums', # NEW
'item_creer_fichiers_authent' => '创建 .htpasswd 文件',
'item_desactiver_forum_administrateur' => '使管理论坛不可用',
'item_gerer_annuaire_site_web' => '管理站点目录',
'item_gerer_statistiques' => '管理统计',
'item_limiter_recherche' => '限制搜索你站点包括的内容',
'item_login' => '登录',
'item_messagerie_agenda' => 'Activer la messagerie et l’agenda', # NEW
'item_mots_cles_association_articles' => '文章',
'item_mots_cles_association_rubriques' => '相关专栏',
'item_mots_cles_association_sites' => '参与或联合的站点.',
'item_non' => 'No',
'item_non_accepter_inscriptions' => '不允许注册',
'item_non_activer_messages_avertissement' => '没有警告信息',
'item_non_afficher_calendrier' => '在日历中不显示',
'item_non_ajout_mots_cles' => '不认证论坛的新关键词',
'item_non_autoriser_documents_joints' => '不论证文章中文档',
'item_non_autoriser_documents_joints_rubriques' => '不认证专栏中的文档',
'item_non_autoriser_selectionner_date_en_ligne' => 'La date des documents est celle de leur ajout sur le site', # NEW
'item_non_autoriser_syndication_integrale' => 'Ne diffuser qu\'un résumé', # NEW
'item_non_bloquer_liens_syndiques' => '不阻止联合中引出的链接',
'item_non_compresseur' => 'Désactiver la compression', # NEW
'item_non_config_forums_prive_global' => 'Désactiver le forum des rédacteurs', # NEW
'item_non_config_forums_prive_objets' => 'Désactiver ces forums', # NEW
'item_non_creer_fichiers_authent' => '不创建这些文件',
'item_non_gerer_annuaire_site_web' => '使网站目录不可用',
'item_non_gerer_statistiques' => '不管理统计表',
'item_non_limiter_recherche' => '扩充搜索到参考站点',
'item_non_messagerie_agenda' => 'Désactiver la messagerie et l’agenda', # NEW
'item_non_publier_articles' => '不发表出版日期前的文章.',
'item_non_utiliser_config_groupe_mots_cles' => '不使用关键词的高级配置',
'item_non_utiliser_moteur_recherche' => '不使用引擎',
'item_non_utiliser_mots_cles' => '不使用关键词',
'item_non_utiliser_syndication' => '不使用自动联合',
'item_nouvel_auteur' => '新作者',
'item_nouvelle_rubrique' => '新专栏',
'item_oui' => '是',
'item_publier_articles' => '忽略出版日期出版文章.',
'item_reponse_article' => '回复文章',
'item_utiliser_config_groupe_mots_cles' => '使用关键词高级配置',
'item_utiliser_moteur_recherche' => '使用搜索引擎',
'item_utiliser_mots_cles' => '使用关键词',
'item_utiliser_syndication' => '使用自动联合',
'item_version_html_max_html4' => 'Se limiter au HTML4 sur le site public', # NEW
'item_version_html_max_html5' => 'Permettre le HTML5', # NEW
'item_visiteur' => '访问者',

// J
'jour_non_connu_nc' => '不知道',

// L
'label_bando_outils' => 'Barre d\'outils', # NEW
'label_bando_outils_afficher' => 'Afficher les outils', # NEW
'label_bando_outils_masquer' => 'Masquer les outils', # NEW
'label_choix_langue' => 'Selectionnez votre langue', # NEW
'label_nom_fichier_connect' => 'Indiquez le nom utilisé pour ce serveur', # NEW
'label_slogan_site' => 'Slogan du site', # NEW
'label_taille_ecran' => 'Largeur de l\'ecran', # NEW
'label_texte_et_icones_navigation' => 'Menu de navigation', # NEW
'label_texte_et_icones_page' => 'Affichage dans la page', # NEW
'ldap_correspondance' => 'héritage du champ @champ@', # NEW
'ldap_correspondance_1' => 'Héritage des champs LDAP', # NEW
'ldap_correspondance_2' => 'Pour chacun des champs SPIP suivants, indiquer le nom du champ LDAP correspondant. Laisser vide pour ne pas le remplir, séparer par des espaces ou des virgules pour essayer plusieurs champs LDAP.', # NEW
'lien_ajout_destinataire' => '加接收者',
'lien_ajouter_auteur' => '加作者',
'lien_ajouter_mot' => 'Ajouter ce mot-clé', # NEW
'lien_ajouter_participant' => '添加一个新的参与者',
'lien_email' => '电子邮件',
'lien_forum_public' => '管理文章的公共论坛',
'lien_mise_a_jour_syndication' => '现在更新',
'lien_nom_site' => '站点名:',
'lien_nouvelle_recuperation' => '试着重新获取数据',
'lien_reponse_article' => '回应文章',
'lien_reponse_rubrique' => '回应专栏',
'lien_reponse_site_reference' => '回应到参考站点:',
'lien_retirer_auteur' => '移去作者',
'lien_retirer_tous_auteurs' => 'Retirer tous les auteurs', # NEW
'lien_retrait_particpant' => '移去参与者',
'lien_site' => '站点',
'lien_supprimer_rubrique' => '删除此栏',
'lien_tout_deplier' => '展开所有',
'lien_tout_replier' => '伸缩所有',
'lien_tout_supprimer' => 'Tout supprimer', # NEW
'lien_trier_nom' => '按名字排序',
'lien_trier_nombre_articles' => '按文章号排序',
'lien_trier_statut' => '按标题排序',
'lien_voir_en_ligne' => '在线预览:',
'logo_article' => '文章图标',
'logo_auteur' => '作者图标',
'logo_groupe' => 'LOGO DE CE GROUPE', # NEW
'logo_mot_cle' => '关键词图标',
'logo_rubrique' => '专栏图标',
'logo_site' => '站点图标',
'logo_standard_rubrique' => '专栏标准图标',
'logo_survol' => '盘旋图标',

// M
'menu_aide_installation_choix_base' => '选择数据库',
'module_fichier_langue' => '语言文件',
'module_raccourci' => '快捷方式',
'module_texte_affiche' => '显示文本',
'module_texte_explicatif' => '你不能插入快捷方式到站点模板. 有一种语言他们将自动翻译为各种语言.',
'module_texte_traduction' => '语言文件 « @module@ » 可用在:',
'mois_non_connu' => '不知道',

// N
'nouvelle_version_spip' => 'La version @version@ de SPIP est disponible', # NEW

// O
'onglet_contenu' => 'Contenu', # NEW
'onglet_declarer_une_autre_base' => 'Déclarer une autre base', # NEW
'onglet_discuter' => 'Discuter', # NEW
'onglet_documents' => 'Documents', # NEW
'onglet_interactivite' => 'Interactivité', # NEW
'onglet_proprietes' => 'Propriétés', # NEW
'onglet_repartition_actuelle' => '现在',
'onglet_sous_rubriques' => 'Sous-rubriques', # NEW

// P
'page_pas_proxy' => 'Cette page ne doit pas passer par le proxy', # NEW
'pas_de_proxy_pour' => 'Au besoin, indiquez les machines ou domaines pour lesquels ce proxy ne doit pas s\'appliquer (par exemple : @exemple@)', # NEW
'plugin_charge_paquet' => 'Chargement du paquet @name@', # NEW
'plugin_charger' => 'Télécharger', # NEW
'plugin_erreur_charger' => 'erreur : impossible de charger @zip@', # NEW
'plugin_erreur_droit1' => 'Le répertoire <code>@dest@</code> n\'est pas accessible en écriture.', # NEW
'plugin_erreur_droit2' => 'Veuillez vérifier les droits sur ce répertoire (et le créer le cas échéant), ou installer les fichiers par FTP.', # NEW
'plugin_erreur_zip' => 'echec pclzip : erreur @status@', # NEW
'plugin_etat_developpement' => 'en développement', # NEW
'plugin_etat_experimental' => 'expérimental', # NEW
'plugin_etat_stable' => 'stable', # NEW
'plugin_etat_test' => 'en test', # NEW
'plugin_impossible_activer' => 'Impossible d\'activer le plugin @plugin@', # NEW
'plugin_info_automatique1' => 'Si vous souhaitez autoriser l\'installation automatique des plugins, veuillez :', # NEW
'plugin_info_automatique1_lib' => 'Si vous souhaitez autoriser l\'installation automatique de cette librairie, veuillez :', # NEW
'plugin_info_automatique2' => 'créer un répertoire <code>@rep@</code> ;', # NEW
'plugin_info_automatique3' => 'vérifier que le serveur est autorisé à écrire dans ce répertoire.', # NEW
'plugin_info_automatique_creer' => 'à créer à la racine du site.', # NEW
'plugin_info_automatique_exemples' => 'exemples :', # NEW
'plugin_info_automatique_ftp' => 'Vous pouvez installer des plugins, par FTP, dans le répertoire <tt>@rep@</tt>', # NEW
'plugin_info_automatique_lib' => 'Certains plugins demandent aussi à pouvoir télécharger des fichiers dans le répertoire <code>lib/</code>, à créer le cas échéant à la racine du site.', # NEW
'plugin_info_automatique_liste' => 'Vos listes de plugins :', # NEW
'plugin_info_automatique_liste_officielle' => 'les plugins officiels', # NEW
'plugin_info_automatique_liste_update' => 'Mettre à jour les listes', # NEW
'plugin_info_automatique_ou' => 'ou...', # NEW
'plugin_info_automatique_select' => 'Sélectionnez ci-dessous un plugin : SPIP le téléchargera et l\'installera dans le répertoire <code>@rep@</code> ; si ce plugin existe déjà, il sera mis à jour.', # NEW
'plugin_info_extension_1' => 'Les extensions ci-dessous sont chargées et activées dans le répertoire @extensions@.', # NEW
'plugin_info_extension_2' => 'Elles ne sont pas désactivables.', # NEW
'plugin_info_telecharger' => 'à télécharger depuis @url@ et à installer dans @rep@', # NEW
'plugin_librairies_installees' => 'Librairies installées', # NEW
'plugin_necessite_lib' => 'Ce plugin nécessite la librairie @lib@', # NEW
'plugin_necessite_plugin' => 'Nécessite le plugin @plugin@ en version @version@ minimum.', # NEW
'plugin_necessite_spip' => 'Nécessite SPIP en version @version@ minimum.', # NEW
'plugin_source' => 'source: ', # NEW
'plugin_titre_automatique' => 'Installation automatique', # NEW
'plugin_titre_automatique_ajouter' => 'Ajouter des plugins', # NEW
'plugin_titre_installation' => 'Installation du plugin @plugin@', # NEW
'plugin_zip_active' => 'Continuez pour l\'activer', # NEW
'plugin_zip_adresse' => 'indiquez ci-dessous l\'adresse d\'un fichier zip de plugin à télécharger, ou encore l\'adresse d\'une liste de plugins.', # NEW
'plugin_zip_adresse_champ' => 'Adresse du plugin ou de la liste ', # NEW
'plugin_zip_content' => 'Il contient les fichiers suivants (@taille@),<br />prêts à installer dans le répertoire <code>@rep@</code>', # NEW
'plugin_zip_installe_finie' => 'Le fichier @zip@ a été décompacté et installé.', # NEW
'plugin_zip_installe_rep_finie' => 'Le fichier @zip@ a été décompacté et installé dans le répertoire @rep@', # NEW
'plugin_zip_installer' => 'Vous pouvez maintenant l\'installer.', # NEW
'plugin_zip_telecharge' => 'Le fichier @zip@ a été téléchargé', # NEW
'plugins_actif_aucun' => 'Aucun plugin activé.', # NEW
'plugins_actif_un' => 'Un plugin activé.', # NEW
'plugins_actifs' => '@count@ plugins activés.', # NEW
'plugins_actifs_liste' => 'Plugins actifs', # NEW
'plugins_compte' => '@count@ plugins', # NEW
'plugins_disponible_un' => 'Un plugin disponible.', # NEW
'plugins_disponibles' => '@count@ plugins disponibles.', # NEW
'plugins_erreur' => 'Erreur dans les plugins : @plugins@', # NEW
'plugins_liste' => 'Liste des plugins', # NEW
'plugins_liste_extensions' => 'Extensions', # NEW
'plugins_recents' => 'Plugins récents.', # NEW
'plugins_vue_hierarchie' => 'Hiérarchie', # NEW
'plugins_vue_liste' => 'Liste', # NEW
'protocole_ldap' => 'Version du protocole :', # NEW

// Q
'queue_executer_maintenant' => 'Exécuter maintenant', # NEW
'queue_nb_jobs_in_queue' => '@nb@ travaux en attente', # NEW
'queue_next_job_in_nb_sec' => 'Prochain travail dans @nb@ s', # NEW
'queue_one_job_in_queue' => '1 travail en attente', # NEW
'queue_purger_queue' => 'Purger la liste des travaux', # NEW
'queue_titre' => 'Liste de travaux', # NEW

// R
'repertoire_plugins' => 'Répertoire :', # NEW

// S
'sans_heure' => 'sans heure', # NEW
'statut_admin_restreint' => '(受限制的管理)',
'syndic_choix_moderation' => 'Que faire des prochains liens en provenance de ce site ?', # NEW
'syndic_choix_oublier' => 'Que faire des liens qui ne figurent plus dans le fichier de syndication ?', # NEW
'syndic_choix_resume' => 'Certains sites diffusent le texte complet des articles. Lorsque celui-ci est disponible souhaitez-vous syndiquer :', # NEW
'syndic_lien_obsolete' => 'lien obsolète', # NEW
'syndic_option_miroir' => 'les bloquer automatiquement', # NEW
'syndic_option_oubli' => 'les effacer (après @mois@ mois)', # NEW
'syndic_option_resume_non' => 'le contenu complet des articles (au format HTML)', # NEW
'syndic_option_resume_oui' => 'un simple résumé (au format texte)', # NEW
'syndic_options' => 'Options de syndication :', # NEW

// T
'taille_cache_image' => 'Les images calculées automatiquement par SPIP (vignettes des documents, titres présentés sous forme graphique, fonctions mathématiques au format TeX...) occupent dans le répertoire @dir@ un total de @taille@.', # NEW
'taille_cache_infinie' => '本网站对 <code>CACHE/</code>目录的大小没有限制。', # MODIF
'taille_cache_maxi' => '网络文章发布系统将尝试限制 <code>CACHE/</code> 目录的大小至大约 <b>@octets@</b> 数据.', # MODIF
'taille_cache_octets' => '缓存目录当前的大小是 @octets@。',
'taille_cache_vide' => '缓存当前状态为空。',
'taille_repertoire_cache' => '缓存目录的大小',
'text_article_propose_publication' => '文章已提交发表. 不要犹豫通过论坛发表你的观点附在文章后 (在页底).', # MODIF
'text_article_propose_publication_forum' => 'N\'hésitez pas à donner votre avis grâce au forum attaché à cet article (en bas de page).', # NEW
'texte_acces_ldap_anonyme_1' => '一些 LDAP 服务器不允许任何匿名访问. 这样你必须标识初始连接,以后能搜索目录中信息. 无论如何, 大多数情况下以下区域可留空.',
'texte_admin_effacer_01' => '命令删除数据库的<i>所有</i> 内容包括
<i>所有</i> 访问者和管理者的访问参数. 执行后, 为新建数据库和第一个管理员访问你应
重新安装 SPIP .',
'texte_admin_effacer_stats' => 'Cette commande efface toutes les données liées aux statistiques de visite du site, y compris la popularité des articles.', # NEW
'texte_adresse_annuaire_1' => '( 如果你的目录安装到同样机器作为WEB站点, 可能 «localhost».)',
'texte_ajout_auteur' => '以下作者加到文章:',
'texte_annuaire_ldap_1' => '若你有权访问(LDAP) 目录, 你可用它在SPIP下自动导入用户.',
'texte_article_statut' => '文章是:',
'texte_article_virtuel' => '虚文章',
'texte_article_virtuel_reference' => '<b>虚文章 :</b>在SPIP中引用文档, 但是重定向到其它的URL. 移去链接, 删除以下 URL.',
'texte_aucun_resultat_auteur' => '"@cherche_auteur@"没有结果.',
'texte_auteur_messagerie' => '<MODIF>站点能连续监控连接编辑列表, 它允许实时交换信息 (如果以上消息被禁, 连接编辑列表自身禁用). 你能决定不出现在列表中 (其他用户在列表中" 无法 "看到你）',
'texte_auteur_messagerie_1' => '本站点开放注册会员的短消息及私人论坛的交流.您可以选择不参与讨论交流.',
'texte_auteurs' => '作者',
'texte_choix_base_1' => '选择你的数据库:',
'texte_choix_base_2' => 'SQL 服务器包括几个数据库.',
'texte_choix_base_3' => '<b>选择</b> 以下主机给你提供的这个:', # MODIF
'texte_choix_table_prefix' => 'Préfixe des tables :', # NEW
'texte_commande_vider_tables_indexation' => '使用命令清空被SPIP集成的搜索引擎用到的索引表
   . 它将允许你保留磁盘空间
   .',
'texte_comment_lire_tableau' => '根据流行程序的不同文章的等级,
  , 在页边标识 
  ; 文章流行度 (
   如果正常带宽维护每天的访问者数量
  ) 并且访问者数量记录
  自从鼠标开始移过标题显示在气球上
  .',
'texte_compacter_avertissement' => 'Attention à ne pas activer ces options durant le développement de votre site : les éléments compactés perdent toute lisibilité.', # NEW
'texte_compacter_script_css' => 'SPIP peut compacter les scripts javascript et les feuilles de style CSS, pour les enregistrer dans des fichiers statiques ; cela accélère l\'affichage du site.', # NEW
'texte_compatibilite_html' => 'Vous pouvez demander à SPIP de produire, sur le site public, du code compatible avec la norme <i>HTML4</i>, ou lui permettre d\'utiliser les possibilités plus modernes du <i>HTML5</i>.', # NEW
'texte_compatibilite_html_attention' => 'Il n\'y a aucun risque à activer l\'option <i>HTML5</i>, mais si vous le faites, les pages de votre site devront commencer par la mention suivante pour rester valides : <code>&lt;!DOCTYPE html&gt;</code>.', # NEW
'texte_compresse_ou_non' => '(这个被压缩或没有)',
'texte_compresseur_page' => 'SPIP peut compresser automatiquement chaque page qu\'il envoie aux
visiteurs du site. Ce réglage permet d\'optimiser la bande passante (le
site est plus rapide derrière une liaison à faible débit), mais
demande plus de puissance au serveur.', # NEW
'texte_compte_element' => '@count@ 元素',
'texte_compte_elements' => '@count@ 元素',
'texte_config_groupe_mots_cles' => '你愿意激活关键词组的高级配置,
   详细说明, 例如每一组中能选中单一字
   ,一个组很重要...?', # MODIF
'texte_conflit_edition_correction' => 'Veuillez contrôler ci-dessous les différences entre les deux versions du texte ; vous pouvez aussi copier vos modifications, puis recommencer.', # NEW
'texte_connexion_mysql' => '根据你主机提到的信息: 它将给你, 如果你的主机支持 SQL,SQL 服务器的连接码.', # MODIF
'texte_contenu_article' => '(简要说明文章的内容.)',
'texte_contenu_articles' => '基于为你选择的站点的展开, 你能决定
  一些文章元素没有用.
  用以下列表选择哪一个元素将可用.',
'texte_crash_base' => '如果数据库毁坏
   , 你可以自动修复
   它.',
'texte_creer_rubrique' => '在写文章前,<br />您必须创建一个专栏.', # MODIF
'texte_date_creation_article' => '创建文章日期:',
'texte_date_publication_anterieure' => '更早的出版日期', # MODIF
'texte_date_publication_anterieure_nonaffichee' => '隐藏更早的出版日期.', # MODIF
'texte_date_publication_article' => '<MODIF>在线出版日期:',
'texte_descriptif_petition' => '请求说明',
'texte_descriptif_rapide' => '主要描述',
'texte_documents_joints' => '您可以允许添加文档 (office 文件, 图像, 多媒体等.) 到文章和专栏. 这些文档能在文章中引用或单独显示.<p>', # MODIF
'texte_documents_joints_2' => '这个设置不阻止直接插入图片到文件.',
'texte_effacer_base' => '删除SPIP 数据库',
'texte_effacer_donnees_indexation' => '删除索引文件',
'texte_effacer_statistiques' => 'Effacer les statistiques', # NEW
'texte_en_cours_validation' => '下列文章和新闻提交出版. 请不要犹豫通过论坛发表您的观点.', # MODIF
'texte_en_cours_validation_forum' => 'N\'hésitez pas à donner votre avis grâce aux forums qui leur sont attachés.', # NEW
'texte_enrichir_mise_a_jour' => '你可以丰富你的文本,通过«文字快捷方式».',
'texte_fichier_authent' => '<b>让SPIP创建特殊的<tt>.htpasswd</tt>
  并且<tt>.htpasswd-admin</tt> 文件在目录@dossier@?</b><p>
  这些文件能用于严格限制访问作者和管理者
  在站点的不同部分
  (例如, 外部统计编程).<p>
  如果你没有用这样的文件, 留下该选项为它的缺省值
   (没有建
  文件).', # MODIF
'texte_informations_personnelles_1' => '系统将提供给你提供定制访问.',
'texte_informations_personnelles_2' => '(注意: 如果是重新安装, 你的访问正在工作, 你可以',
'texte_introductif_article' => '(文章介绍.)',
'texte_jeu_caractere' => '如果你的站点显示的字符不同于罗马数字(就是 «western»)
 这个选项很有用.
 这种情况下, 为使用合适的字符集缺省设置必须改变
; 无论如何, 我们建议你试试不同的字符符集
 . 如果你修改参数, 不要忘记, 
 根据 (<tt>#CHARSET</tt> 标记)协调公共站点.', # MODIF
'texte_jeu_caractere_2' => '设置没有生效.
 因此, 已输入的文本可能不能正常显示
 在修改设置后. 无论如何,
 你可返回到以前的设置.', # MODIF
'texte_jeu_caractere_3' => 'Votre site est actuellement installé dans le jeu de caractères :', # NEW
'texte_jeu_caractere_4' => 'Si cela ne correspond pas à la réalité de vos données (suite, par exemple, à une restauration de base de données), ou si <em>vous démarrez ce site</em> et souhaitez partir sur un autre jeu de caractères, veuillez indiquer ce dernier ici :', # NEW
'texte_jeu_caractere_conversion' => 'Note : vous pouvez décider de convertir une fois pour toutes l\'ensemble des textes de votre site (articles, brèves, forums, etc.) vers l\'alphabet <tt>utf-8</tt>, en vous rendant sur <a href="@url@">la page de conversion vers l\'utf-8</a>.', # NEW
'texte_lien_hypertexte' => '(如果消息引用了一个WEB站点的文章, 或页面, 请提供页面标题和 URL.)',
'texte_liens_sites_syndiques' => '从联合站点发出的连接能
   被预先阻止; 以下
   设置允许联合站点创建后
   显示缺省设置. 
   然后无论如何可分开阻止每个连接
   , 或选择,
   对每一站点, 阻止连接来自
   任何特别的站点.',
'texte_login_ldap_1' => '(匿名访问留空或输入完整路径, 例如 «<tt>uid=smith, ou=users, dc=my-domain, dc=com</tt>».)',
'texte_login_precaution' => '警告 ! 这是你正连接的登录.
 小心使用这个表单...',
'texte_message_edit' => '警告: 消息可被所有站点管理员管理, 对所有编辑显示. 使用声明只加重了站点的重要事件.',
'texte_messagerie_agenda' => 'Une messagerie permet aux rédacteurs du site de communiquer entre eux directement dans l’espace privé du site. Elle est associée à un agenda.', # NEW
'texte_messages_publics' => '文章的公共消息:',
'texte_mise_a_niveau_base_1' => '你已更新 SPIP 文件.
 现在你必须更新站点
 数据库.',
'texte_modifier_article' => '修改文章:',
'texte_moteur_recherche_active' => '<b>搜索引擎激活了.</b> 
  如果你执行快速索引使用这个命令 (例如恢复
  备份后). 你应注意文章用正常方式修改
   (从SPIP界面) 被重新正常索引
  : 因此这个命令只在异常情况下有用.',
'texte_moteur_recherche_non_active' => '搜索引擎未激活.',
'texte_mots_cles' => '关键词允许您创建与所处专栏位置无关的独立文章间的主题相关连接. 
这种方法能丰富站点的导航能力,甚至能使用这种属性定制您模板中的文章.',
'texte_mots_cles_dans_forum' => '你愿意用户选择使用关键词, 在公众论坛中? (警告: 选项正确使用会更复杂.)', # MODIF
'texte_multilinguisme' => '如果您希望用复杂导航管理多语言文章, 您可以根据站点的组织, 在文章及/或专栏中添加语言选择菜单.',
'texte_multilinguisme_trad' => '同样,在不同的文章翻译中你可以激活连接管理系统.',
'texte_non_compresse' => '<i>未解压</i> (你的服务器不支持)',
'texte_non_fonction_referencement' => '你可以选择不使用这个自动特性, 手动输入连接元素...',
'texte_nouveau_message' => '新消息',
'texte_nouveau_mot' => '新关键词',
'texte_nouvelle_version_spip_1' => '您已经安装了新版SPIP.',
'texte_nouvelle_version_spip_2' => '新版本需要比通常更彻底的更新. 如果你是站点管理员, 请删除目录中 <tt>ecrire</tt>文件 <tt>inc_connect.php3</tt>  并重新安装更新你的数据库连接参数. <p>(NB.: 如果你忘记了连接参数, 在删除前看看<tt>inc_connect.php3</tt> ...)', # MODIF
'texte_operation_echec' => '返回前页,选择另一个数据库或新建一个. 确认你主机提供的信息.',
'texte_plus_trois_car' => '多于 3 字符',
'texte_plusieurs_articles' => '"@cherche_auteur@好几个作者找到了":',
'texte_port_annuaire' => '(一般缺省值更合适.)',
'texte_presente_plugin' => 'Cette page liste les plugins disponibles sur le site. Vous pouvez activer les plugins nécessaires en cochant la case correspondante.', # NEW
'texte_proposer_publication' => '当你的文章完成,<br /> 你可提交出版.', # MODIF
'texte_proxy' => '一些情况下 (内部网, 受保护的网络...),
  有必要用 <i>代理HTTP</i> 到达联合站点.
  只要有一个代理就在以下输入一个地址, 因此
  <tt><html>http://proxy:8080</html></tt>. 一般地,
  你可以留空.', # MODIF
'texte_publication_articles_post_dates' => 'SPIP将采纳提供的将来
  出版的文章
  什么行为?',
'texte_rappel_selection_champs' => '[记住正确选择区域.]',
'texte_recalcul_page' => '如果你只要刷新
这页, 最好在公共区做,使用按钮 « refresh ».',
'texte_recapitiule_liste_documents' => '该页将汇总各专栏中的文档. 如需修改各个文档的信息, 单击所属专栏页面的链接.',
'texte_recuperer_base' => '修复数据库',
'texte_reference_mais_redirige' => '你的SPIP参考的文章, 但是重定向到别的 URL.',
'texte_referencement_automatique' => '<b>自动站点引用</b><br />通过指出以下的想得到的URL或后端文件的地址,您可以迅速引用一个站点. SPIP 将自动获得关于站点的信息 (标题, 描述...).', # MODIF
'texte_referencement_automatique_verifier' => 'Veuillez vérifier les informations fournies par <tt>@url@</tt> avant d\'enregistrer.', # NEW
'texte_requetes_echouent' => '<b>当一些 SQL 查询失败并且没有任何原因显示
  , 可能是数据库
  自动出错了
  .</b>
  <p>SQL 有修复表的配置
  当它们被偶然打断.
  在这里, 你可以执行修复;
  为避免失败, 你应保持显示的备份, 这将包含
  出错的线索...
  <p>如果问题仍然存在,请联系
  主机.', # MODIF
'texte_selection_langue_principale' => '你可在下面选择"主要语言". 幸运地,选择不限制你的文章使用选中的语言,但允许确定

<ul><li> 公众站点的缺省日期格式</li>

<li> 文字引擎将用于SPIP自动翻译;</li>

<li> 公众站点上论坛的语言</li>

<li> 私有区显示缺省语言.</li></ul>',
'texte_signification' => '<MODIF>深色条代表条目总数（子专栏总数），浅色条代表各个专栏的访问人数.',
'texte_sous_titre' => '子标题',
'texte_statistiques_visites' => '(黑线:  周日 / 夜晚 曲线: 平均进展)',
'texte_statut_attente_validation' => '未确认',
'texte_statut_publies' => '在线出版',
'texte_statut_refuses' => '丢弃',
'texte_suppression_fichiers' => '使用命令删除SPIP缓存中的文件
这允许你, 另外地, 以防你进入站点结构和图片重要修改后
强制你刷新所有的页面.',
'texte_sur_titre' => '顶标题',
'texte_syndication' => '如果站点允许, 可以自动得到最新的素材
  . 要这样的话, 你必须激活联合. 
  <blockquote><i>一些主机禁用这个功能; 
  这种情况下, 你不能使用
  你站点的内容联合.</i></blockquote>', # MODIF
'texte_table_ok' => ': 表好了.',
'texte_tables_indexation_vides' => '引擎的索引表为空.',
'texte_tentative_recuperation' => '试图修复',
'texte_tenter_reparation' => '试图修复数据库',
'texte_test_proxy' => '若使用代理, 输入要测试的
      网站地址.',
'texte_titre_02' => '主题:',
'texte_titre_obligatoire' => '<b>标题</b> [必需]', # MODIF
'texte_travail_article' => '@nom_auteur_modif@  @date_diff@ 分钟前正在修改这篇文章',
'texte_travail_collaboratif' => '如果经常好几个作者编辑同一文章
  ,系统能显示最近的文章 
  «opened» 文章
  为避免同时修改.
  该选项为避免不合时宜的警告信息缺省
  设定为
  不可用.',
'texte_trop_resultats_auteurs' => '搜索到 "@cherche_auteur@"太多结果; 请重新定义搜索.',
'texte_type_urls' => 'Vous pouvez choisir ci-dessous le mode de calcul de l\'adresse des pages.', # NEW
'texte_type_urls_attention' => 'Attention ce réglage ne fonctionnera que si le fichier @htaccess@ est correctement installé à la racine du site.', # NEW
'texte_unpack' => '正下载最新版本',
'texte_utilisation_moteur_syndiques' => '当你使用集成到SPIP的搜索引擎, 你可以执行搜索携带站点和不同方式联合的文章. <br /><img src=\'puce.gif\'>最简单的是只搜索文章的标题和描述. <br /><img src=\'puce.gif\'> 第二种方法, 更强有力, 允许SPIP搜索参考站点的文本. 如果你引用了站点, SPIP 将执行搜索站点的文本.', # MODIF
'texte_utilisation_moteur_syndiques_2' => '该方法强制 SPIP 定期访问参考站点,这将使你自己的站点性能降低.',
'texte_vide' => '清空',
'texte_vider_cache' => '清空缓存',
'titre_admin_effacer' => '技术维护',
'titre_admin_tech' => '技术维护',
'titre_admin_vider' => '技术维护',
'titre_ajouter_un_auteur' => 'Ajouter un auteur', # NEW
'titre_ajouter_un_mot' => 'Ajouter un mot-clé', # NEW
'titre_articles_syndiques' => '剔除站点的联合文章',
'titre_cadre_afficher_article' => '显示文章:',
'titre_cadre_afficher_traductions' => '显示语言的翻译状态.',
'titre_cadre_ajouter_auteur' => '加作者:',
'titre_cadre_forum_administrateur' => '管理者私有论坛',
'titre_cadre_forum_interne' => '内部论坛',
'titre_cadre_interieur_rubrique' => '在专栏内部',
'titre_cadre_numero_auteur' => '作者号',
'titre_cadre_numero_objet' => '@objet@ NUMÉRO :', # NEW
'titre_cadre_signature_obligatoire' => '<b>签名</b> [必需]<br />', # MODIF
'titre_compacter_script_css' => 'Compactage des scripts et CSS', # NEW
'titre_compresser_flux_http' => 'Compression du flux HTTP', # NEW
'titre_config_contenu_notifications' => 'Notifications', # NEW
'titre_config_contenu_prive' => 'Dans l’espace privé', # NEW
'titre_config_contenu_public' => 'Sur le site public', # NEW
'titre_config_fonctions' => '站点配置',
'titre_config_forums_prive' => 'Forums de l’espace privé', # NEW
'titre_config_groupe_mots_cles' => '配置关键词组',
'titre_config_langage' => 'Configurer la langue', # NEW
'titre_configuration' => '站点配置',
'titre_configurer_preferences' => 'Configurer vos préférences', # NEW
'titre_conflit_edition' => 'Conflit lors de l\'édition', # NEW
'titre_connexion_ldap' => '选项: <b>你的 LDAP 连接</b>',
'titre_dernier_article_syndique' => '最后联合的文章',
'titre_documents_joints' => '附加文档',
'titre_evolution_visite' => '访问者评估',
'titre_forum_suivi' => '论坛跟踪',
'titre_gauche_mots_edit' => '关键词号:',
'titre_groupe_mots' => '关键词组:',
'titre_identite_site' => 'Identité du site', # NEW
'titre_langue_article' => '文章语言',
'titre_langue_rubrique' => '专栏使用的语言',
'titre_langue_trad_article' => '文章语言和译文',
'titre_les_articles' => '文章',
'titre_messagerie_agenda' => 'Messagerie et agenda', # NEW
'titre_mots_cles_dans_forum' => '公众论坛的关键词',
'titre_mots_tous' => '关键词',
'titre_naviguer_dans_le_site' => '浏览站点...',
'titre_nouveau_groupe' => '新组',
'titre_nouvelle_rubrique' => '新专栏',
'titre_numero_rubrique' => '专栏编号:',
'titre_page_admin_effacer' => '技术维护:删除数据库',
'titre_page_articles_edit' => '修改: @titre@',
'titre_page_articles_page' => '文章',
'titre_page_articles_tous' => '整个站点',
'titre_page_auteurs' => '访问者',
'titre_page_calendrier' => '日历 @nom_mois@ @annee@',
'titre_page_config_contenu' => '站点配置',
'titre_page_config_fonctions' => '站点配置',
'titre_page_configuration' => '站点配置',
'titre_page_controle_petition' => '跟踪请求',
'titre_page_delete_all' => '所有和不能撤回的删除',
'titre_page_documents_liste' => '专栏文档',
'titre_page_forum' => '管理论坛',
'titre_page_forum_envoi' => '发送消息',
'titre_page_forum_suivi' => '论坛跟踪',
'titre_page_index' => '您的私有区',
'titre_page_message_edit' => '写消息',
'titre_page_messagerie' => '您的消息',
'titre_page_mots_tous' => '关键词',
'titre_page_recherche' => '搜索结果@recherche@',
'titre_page_sites_tous' => '参考站点',
'titre_page_statistiques' => '按专栏统计',
'titre_page_statistiques_messages_forum' => 'Messages de forum', # NEW
'titre_page_statistiques_referers' => '统计(引入链接)',
'titre_page_statistiques_signatures_jour' => 'Nombre de signatures par jour', # NEW
'titre_page_statistiques_signatures_mois' => 'Nombre de signatures par mois', # NEW
'titre_page_statistiques_visites' => '访问者统计',
'titre_page_upgrade' => 'SPIP升级 ',
'titre_publication_articles_post_dates' => '发表日期文章的出版物',
'titre_referencement_sites' => '参考站点和联合组织',
'titre_referencer_site' => '参考站点:',
'titre_rendez_vous' => '约会:',
'titre_reparation' => '修复',
'titre_site_numero' => '站点号:',
'titre_sites_proposes' => '已提交站点',
'titre_sites_references_rubrique' => '此栏下的参考站点',
'titre_sites_syndiques' => '联合站点',
'titre_sites_tous' => '参考站点',
'titre_suivi_petition' => '跟踪请求',
'titre_syndication' => '站点联合',
'titre_type_urls' => 'Type d\'adresses URL', # NEW
'tls_ldap' => 'Transport Layer Security :', # NEW
'tout_dossier_upload' => 'Tout le dossier @upload@', # NEW
'trad_article_inexistant' => '没有文章为该号',
'trad_article_traduction' => '这篇文章的所有版本:',
'trad_deja_traduit' => '该文是一篇译文', # MODIF
'trad_delier' => '取消这篇文章到它的译文的链接', # MODIF
'trad_lier' => '该篇文章译自文章No.',
'trad_new' => '为该篇文章写一篇新译文', # MODIF

// U
'upload_fichier_zip' => '压缩文件ZIP',
'upload_fichier_zip_texte' => '您建议安装的文件是ZIP格式压缩文件。',
'upload_fichier_zip_texte2' => '该文件可以被：',
'upload_info_mode_document' => 'Déposer cette image dans le portfolio', # NEW
'upload_info_mode_image' => 'Retirer cette image du portfolio', # NEW
'upload_limit' => '该文件的大小超过了服务器允许的范围，服务器允许<i>upload</i>的最大文件是 @max@.',
'upload_zip_conserver' => 'Conserver l’archive après extraction', # NEW
'upload_zip_decompacter' => '已被解压，并且其中的所有文件均被安装到网站。将被安装到网站的文件如下：',
'upload_zip_telquel' => '照原样安装，仍以 Zip压缩文档格式;',
'upload_zip_titrer' => 'Titrer selon le nom des fichiers', # NEW
'utf8_convert_attendez' => 'Attendez quelques instants et rechargez cette page.', # NEW
'utf8_convert_avertissement' => 'Vous vous apprêtez à convertir le contenu de votre base de données (articles, brèves, etc) du jeu de caractères <b>@orig@</b> vers le jeu de caractères <b>@charset@</b>.', # NEW
'utf8_convert_backup' => 'N\'oubliez pas de faire auparavant une sauvegarde complète de votre site. Vous devrez aussi vérifier que vos squelettes et fichiers de langue sont compatibles @charset@.', # NEW
'utf8_convert_erreur_deja' => 'Votre site est déjà en @charset@, inutile de le convertir...', # NEW
'utf8_convert_erreur_orig' => 'Erreur : le jeu de caractères @charset@ n\'est pas supporté.', # NEW
'utf8_convert_termine' => 'C\'est terminé !', # NEW
'utf8_convert_timeout' => '<b>Important :</b> en cas de <i>timeout</i> du serveur, veuillez recharger la page jusqu\'à ce qu\'elle indique « terminé ».', # NEW
'utf8_convert_verifier' => 'Vous devez maintenant aller vider le cache, et vérifier que tout se passe bien sur les pages publiques du site. En cas de gros problème, une sauvegarde de vos données a été réalisée (au format SQL) dans le répertoire @rep@.', # NEW
'utf8_convertir_votre_site' => 'Convertir votre site en utf-8', # NEW

// V
'version' => 'Version :', # NEW
'version_deplace_rubrique' => 'Déplacé de <b>« @from@ »</b> vers <b>« @to@ »</b>.', # NEW
'version_initiale' => '原版本'
);

?>
