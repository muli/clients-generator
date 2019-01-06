// ===================================================================================================
//                           _  __     _ _
//                          | |/ /__ _| | |_ _  _ _ _ __ _
//                          | ' </ _` | |  _| || | '_/ _` |
//                          |_|\_\__,_|_|\__|\_,_|_| \__,_|
//
// This file is part of the Kaltura Collaborative Media Suite which allows users
// to do with audio, video, and animation what Wiki platfroms allow them to do with
// text.
//
// Copyright (C) 2006-2018  Kaltura Inc.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// @ignore
// ===================================================================================================
using System;
using System.Collections.Generic;
using System.Xml;
using System.Runtime.Serialization;
using System.Text.RegularExpressions;
using Kaltura.Types;

namespace Kaltura
{
	public static class ObjectFactory
	{
		private static Regex prefixRegex = new Regex("^Kaltura");
		
		public static T Create<T>(XmlElement xmlElement) where T : ObjectBase
		{
			if (xmlElement["objectType"] == null)
			{
				return null;
			}
				
			var className = xmlElement["objectType"].InnerText;
			className = prefixRegex.Replace(className, "");
			
			var type = Type.GetType("Kaltura.Types." + className);
			if (type == null)
			{
				type = typeof(T);
			}
			
			if (type == null)
				throw new SerializationException("Invalid object type");
			
			return (T)System.Activator.CreateInstance(type, xmlElement);
		}
		public static T Create<T>(IDictionary<string,object> data) where T : ObjectBase
		{
			if (data == null || data["objectType"] == null)
			{
				return null;
			}
				
			var className = (string)data["objectType"];
			className = prefixRegex.Replace(className, "");
			
			var type = Type.GetType("Kaltura.Types." + className);
			if (type == null)
			{
				type = typeof(T);
			}
			
			if (type == null)
				throw new SerializationException("Invalid object type");
			
			return (T)System.Activator.CreateInstance(type, data);
		}
		
		public static IListResponse Create(XmlElement xmlElement)
		{
			if (xmlElement["objectType"] == null)
			{
				return null;
			}
			
			string className = xmlElement["objectType"].InnerText;
			switch (className)
			{
				case "KalturaSocialCommentListResponse":
					return new ListResponse<SocialComment>(xmlElement);
				case "KalturaSocialFriendActivityListResponse":
					return new ListResponse<SocialFriendActivity>(xmlElement);
				case "KalturaSocialActionListResponse":
					return new ListResponse<SocialAction>(xmlElement);
				case "KalturaHouseholdPaymentMethodListResponse":
					return new ListResponse<HouseholdPaymentMethod>(xmlElement);
				case "KalturaPaymentMethodProfileListResponse":
					return new ListResponse<PaymentMethodProfile>(xmlElement);
				case "KalturaHouseholdPaymentGatewayListResponse":
					return new ListResponse<HouseholdPaymentGateway>(xmlElement);
				case "KalturaPaymentGatewayProfileListResponse":
					return new ListResponse<PaymentGatewayProfile>(xmlElement);
				case "KalturaHouseholdDeviceListResponse":
					return new ListResponse<HouseholdDevice>(xmlElement);
				case "KalturaHouseholdUserListResponse":
					return new ListResponse<HouseholdUser>(xmlElement);
				case "KalturaHomeNetworkListResponse":
					return new ListResponse<HomeNetwork>(xmlElement);
				case "KalturaConfigurationsListResponse":
					return new ListResponse<Configurations>(xmlElement);
				case "KalturaConfigurationGroupDeviceListResponse":
					return new ListResponse<ConfigurationGroupDevice>(xmlElement);
				case "KalturaConfigurationGroupTagListResponse":
					return new ListResponse<ConfigurationGroupTag>(xmlElement);
				case "KalturaConfigurationGroupListResponse":
					return new ListResponse<ConfigurationGroup>(xmlElement);
				case "KalturaSSOAdapterProfileListResponse":
					return new ListResponse<SSOAdapterProfile>(xmlElement);
				case "KalturaUserInterestListResponse":
					return new ListResponse<UserInterest>(xmlElement);
				case "KalturaFavoriteListResponse":
					return new ListResponse<Favorite>(xmlElement);
				case "KalturaOTTUserListResponse":
					return new ListResponse<OTTUser>(xmlElement);
				case "KalturaPersonalListListResponse":
					return new ListResponse<PersonalList>(xmlElement);
				case "KalturaEngagementListResponse":
					return new ListResponse<Engagement>(xmlElement);
				case "KalturaEngagementAdapterListResponse":
					return new ListResponse<EngagementAdapter>(xmlElement);
				case "KalturaReminderListResponse":
					return new ListResponse<Reminder>(xmlElement);
				case "KalturaInboxMessageListResponse":
					return new ListResponse<InboxMessage>(xmlElement);
				case "KalturaFollowTvSeriesListResponse":
					return new ListResponse<FollowTvSeries>(xmlElement);
				case "KalturaAnnouncementListResponse":
					return new ListResponse<Announcement>(xmlElement);
				case "KalturaPersonalFeedListResponse":
					return new ListResponse<PersonalFeed>(xmlElement);
				case "KalturaTopicListResponse":
					return new ListResponse<Topic>(xmlElement);
				case "KalturaPartnerConfigurationListResponse":
					return new ListResponse<PartnerConfiguration>(xmlElement);
				case "KalturaGenericListResponse":
					return new ListResponse<T>(xmlElement);
				case "KalturaIntegerValueListResponse":
					return new ListResponse<IntegerValue>(xmlElement);
				case "KalturaReportListResponse":
					return new ListResponse<Report>(xmlElement);
				case "KalturaBulkListResponse":
					return new ListResponse<Bulk>(xmlElement);
				case "KalturaSegmentationTypeListResponse":
					return new ListResponse<SegmentationType>(xmlElement);
				case "KalturaUserSegmentListResponse":
					return new ListResponse<UserSegment>(xmlElement);
				case "KalturaSeriesRecordingListResponse":
					return new ListResponse<SeriesRecording>(xmlElement);
				case "KalturaHouseholdPremiumServiceListResponse":
					return new ListResponse<HouseholdPremiumService>(xmlElement);
				case "KalturaCDVRAdapterProfileListResponse":
					return new ListResponse<CDVRAdapterProfile>(xmlElement);
				case "KalturaRecordingListResponse":
					return new ListResponse<Recording>(xmlElement);
				case "KalturaBillingTransactionListResponse":
					return new ListResponse<BillingTransaction>(xmlElement);
				case "KalturaEntitlementListResponse":
					return new ListResponse<Entitlement>(xmlElement);
				case "KalturaAssetFilePpvListResponse":
					return new ListResponse<AssetFilePpv>(xmlElement);
				case "KalturaPpvListResponse":
					return new ListResponse<Ppv>(xmlElement);
				case "KalturaCollectionListResponse":
					return new ListResponse<Collection>(xmlElement);
				case "KalturaDiscountDetailsListResponse":
					return new ListResponse<DiscountDetails>(xmlElement);
				case "KalturaSubscriptionSetListResponse":
					return new ListResponse<SubscriptionSet>(xmlElement);
				case "KalturaProductPriceListResponse":
					return new ListResponse<ProductPrice>(xmlElement);
				case "KalturaCouponsGroupListResponse":
					return new ListResponse<CouponsGroup>(xmlElement);
				case "KalturaPriceDetailsListResponse":
					return new ListResponse<PriceDetails>(xmlElement);
				case "KalturaPricePlanListResponse":
					return new ListResponse<PricePlan>(xmlElement);
				case "KalturaSubscriptionListResponse":
					return new ListResponse<Subscription>(xmlElement);
				case "KalturaProductsPriceListResponse":
					return new ListResponse<ProductPrice>(xmlElement);
				case "KalturaAssetStructMetaListResponse":
					return new ListResponse<AssetStructMeta>(xmlElement);
				case "KalturaMediaFileTypeListResponse":
					return new ListResponse<MediaFileType>(xmlElement);
				case "KalturaChannelListResponse":
					return new ListResponse<Channel>(xmlElement);
				case "KalturaImageListResponse":
					return new ListResponse<Image>(xmlElement);
				case "KalturaRatioListResponse":
					return new ListResponse<Ratio>(xmlElement);
				case "KalturaTagListResponse":
					return new ListResponse<Tag>(xmlElement);
				case "KalturaAssetListResponse":
					return new ListResponse<Asset>(xmlElement);
				case "KalturaAssetStructListResponse":
					return new ListResponse<AssetStruct>(xmlElement);
				case "KalturaImageTypeListResponse":
					return new ListResponse<ImageType>(xmlElement);
				case "KalturaAssetCountListResponse":
					return new ListResponse<AssetsCount>(xmlElement);
				case "KalturaBookmarkListResponse":
					return new ListResponse<Bookmark>(xmlElement);
				case "KalturaAssetCommentListResponse":
					return new ListResponse<AssetComment>(xmlElement);
				case "KalturaAssetStatisticsListResponse":
					return new ListResponse<AssetStatistics>(xmlElement);
				case "KalturaMediaFileListResponse":
					return new ListResponse<MediaFile>(xmlElement);
				case "KalturaAssetHistoryListResponse":
					return new ListResponse<AssetHistory>(xmlElement);
				case "KalturaPlaybackProfileListResponse":
					return new ListResponse<PlaybackProfile>(xmlElement);
				case "KalturaBusinessModuleRuleListResponse":
					return new ListResponse<BusinessModuleRule>(xmlElement);
				case "KalturaDrmProfileListResponse":
					return new ListResponse<DrmProfile>(xmlElement);
				case "KalturaPermissionListResponse":
					return new ListResponse<Permission>(xmlElement);
				case "KalturaMediaConcurrencyRuleListResponse":
					return new ListResponse<MediaConcurrencyRule>(xmlElement);
				case "KalturaAssetUserRuleListResponse":
					return new ListResponse<AssetUserRule>(xmlElement);
				case "KalturaCurrencyListResponse":
					return new ListResponse<Currency>(xmlElement);
				case "KalturaAssetRuleListResponse":
					return new ListResponse<AssetRule>(xmlElement);
				case "KalturaLanguageListResponse":
					return new ListResponse<Language>(xmlElement);
				case "KalturaMetaListResponse":
					return new ListResponse<Meta>(xmlElement);
				case "KalturaDeviceBrandListResponse":
					return new ListResponse<DeviceBrand>(xmlElement);
				case "KalturaCountryListResponse":
					return new ListResponse<Country>(xmlElement);
				case "KalturaOSSAdapterProfileListResponse":
					return new ListResponse<OSSAdapterProfile>(xmlElement);
				case "KalturaSearchHistoryListResponse":
					return new ListResponse<SearchHistory>(xmlElement);
				case "KalturaDeviceFamilyListResponse":
					return new ListResponse<DeviceFamily>(xmlElement);
				case "KalturaRegionListResponse":
					return new ListResponse<Region>(xmlElement);
				case "KalturaUserAssetRuleListResponse":
					return new ListResponse<UserAssetRule>(xmlElement);
				case "KalturaCDNAdapterProfileListResponse":
					return new ListResponse<CDNAdapterProfile>(xmlElement);
				case "KalturaExportTaskListResponse":
					return new ListResponse<ExportTask>(xmlElement);
				case "KalturaExternalChannelProfileListResponse":
					return new ListResponse<ExternalChannelProfile>(xmlElement);
				case "KalturaRecommendationProfileListResponse":
					return new ListResponse<RecommendationProfile>(xmlElement);
				case "KalturaRegistrySettingsListResponse":
					return new ListResponse<RegistrySettings>(xmlElement);
				case "KalturaParentalRuleListResponse":
					return new ListResponse<ParentalRule>(xmlElement);
				case "KalturaUserRoleListResponse":
					return new ListResponse<UserRole>(xmlElement);
			}
		
			return null;
		}
		public static IListResponse Create(IDictionary<string,object> data)
		{
			if (data == null || data["objectType"] == null)
			{
				return null;
			}
			
			string className = (string)data["objectType"];
			switch (className)
			{
				case "KalturaSocialCommentListResponse":
					return new ListResponse<SocialComment>(data);
				case "KalturaSocialFriendActivityListResponse":
					return new ListResponse<SocialFriendActivity>(data);
				case "KalturaSocialActionListResponse":
					return new ListResponse<SocialAction>(data);
				case "KalturaHouseholdPaymentMethodListResponse":
					return new ListResponse<HouseholdPaymentMethod>(data);
				case "KalturaPaymentMethodProfileListResponse":
					return new ListResponse<PaymentMethodProfile>(data);
				case "KalturaHouseholdPaymentGatewayListResponse":
					return new ListResponse<HouseholdPaymentGateway>(data);
				case "KalturaPaymentGatewayProfileListResponse":
					return new ListResponse<PaymentGatewayProfile>(data);
				case "KalturaHouseholdDeviceListResponse":
					return new ListResponse<HouseholdDevice>(data);
				case "KalturaHouseholdUserListResponse":
					return new ListResponse<HouseholdUser>(data);
				case "KalturaHomeNetworkListResponse":
					return new ListResponse<HomeNetwork>(data);
				case "KalturaConfigurationsListResponse":
					return new ListResponse<Configurations>(data);
				case "KalturaConfigurationGroupDeviceListResponse":
					return new ListResponse<ConfigurationGroupDevice>(data);
				case "KalturaConfigurationGroupTagListResponse":
					return new ListResponse<ConfigurationGroupTag>(data);
				case "KalturaConfigurationGroupListResponse":
					return new ListResponse<ConfigurationGroup>(data);
				case "KalturaSSOAdapterProfileListResponse":
					return new ListResponse<SSOAdapterProfile>(data);
				case "KalturaUserInterestListResponse":
					return new ListResponse<UserInterest>(data);
				case "KalturaFavoriteListResponse":
					return new ListResponse<Favorite>(data);
				case "KalturaOTTUserListResponse":
					return new ListResponse<OTTUser>(data);
				case "KalturaPersonalListListResponse":
					return new ListResponse<PersonalList>(data);
				case "KalturaEngagementListResponse":
					return new ListResponse<Engagement>(data);
				case "KalturaEngagementAdapterListResponse":
					return new ListResponse<EngagementAdapter>(data);
				case "KalturaReminderListResponse":
					return new ListResponse<Reminder>(data);
				case "KalturaInboxMessageListResponse":
					return new ListResponse<InboxMessage>(data);
				case "KalturaFollowTvSeriesListResponse":
					return new ListResponse<FollowTvSeries>(data);
				case "KalturaAnnouncementListResponse":
					return new ListResponse<Announcement>(data);
				case "KalturaPersonalFeedListResponse":
					return new ListResponse<PersonalFeed>(data);
				case "KalturaTopicListResponse":
					return new ListResponse<Topic>(data);
				case "KalturaPartnerConfigurationListResponse":
					return new ListResponse<PartnerConfiguration>(data);
				case "KalturaGenericListResponse":
					return new ListResponse<T>(data);
				case "KalturaIntegerValueListResponse":
					return new ListResponse<IntegerValue>(data);
				case "KalturaReportListResponse":
					return new ListResponse<Report>(data);
				case "KalturaBulkListResponse":
					return new ListResponse<Bulk>(data);
				case "KalturaSegmentationTypeListResponse":
					return new ListResponse<SegmentationType>(data);
				case "KalturaUserSegmentListResponse":
					return new ListResponse<UserSegment>(data);
				case "KalturaSeriesRecordingListResponse":
					return new ListResponse<SeriesRecording>(data);
				case "KalturaHouseholdPremiumServiceListResponse":
					return new ListResponse<HouseholdPremiumService>(data);
				case "KalturaCDVRAdapterProfileListResponse":
					return new ListResponse<CDVRAdapterProfile>(data);
				case "KalturaRecordingListResponse":
					return new ListResponse<Recording>(data);
				case "KalturaBillingTransactionListResponse":
					return new ListResponse<BillingTransaction>(data);
				case "KalturaEntitlementListResponse":
					return new ListResponse<Entitlement>(data);
				case "KalturaAssetFilePpvListResponse":
					return new ListResponse<AssetFilePpv>(data);
				case "KalturaPpvListResponse":
					return new ListResponse<Ppv>(data);
				case "KalturaCollectionListResponse":
					return new ListResponse<Collection>(data);
				case "KalturaDiscountDetailsListResponse":
					return new ListResponse<DiscountDetails>(data);
				case "KalturaSubscriptionSetListResponse":
					return new ListResponse<SubscriptionSet>(data);
				case "KalturaProductPriceListResponse":
					return new ListResponse<ProductPrice>(data);
				case "KalturaCouponsGroupListResponse":
					return new ListResponse<CouponsGroup>(data);
				case "KalturaPriceDetailsListResponse":
					return new ListResponse<PriceDetails>(data);
				case "KalturaPricePlanListResponse":
					return new ListResponse<PricePlan>(data);
				case "KalturaSubscriptionListResponse":
					return new ListResponse<Subscription>(data);
				case "KalturaProductsPriceListResponse":
					return new ListResponse<ProductPrice>(data);
				case "KalturaAssetStructMetaListResponse":
					return new ListResponse<AssetStructMeta>(data);
				case "KalturaMediaFileTypeListResponse":
					return new ListResponse<MediaFileType>(data);
				case "KalturaChannelListResponse":
					return new ListResponse<Channel>(data);
				case "KalturaImageListResponse":
					return new ListResponse<Image>(data);
				case "KalturaRatioListResponse":
					return new ListResponse<Ratio>(data);
				case "KalturaTagListResponse":
					return new ListResponse<Tag>(data);
				case "KalturaAssetListResponse":
					return new ListResponse<Asset>(data);
				case "KalturaAssetStructListResponse":
					return new ListResponse<AssetStruct>(data);
				case "KalturaImageTypeListResponse":
					return new ListResponse<ImageType>(data);
				case "KalturaAssetCountListResponse":
					return new ListResponse<AssetsCount>(data);
				case "KalturaBookmarkListResponse":
					return new ListResponse<Bookmark>(data);
				case "KalturaAssetCommentListResponse":
					return new ListResponse<AssetComment>(data);
				case "KalturaAssetStatisticsListResponse":
					return new ListResponse<AssetStatistics>(data);
				case "KalturaMediaFileListResponse":
					return new ListResponse<MediaFile>(data);
				case "KalturaAssetHistoryListResponse":
					return new ListResponse<AssetHistory>(data);
				case "KalturaPlaybackProfileListResponse":
					return new ListResponse<PlaybackProfile>(data);
				case "KalturaBusinessModuleRuleListResponse":
					return new ListResponse<BusinessModuleRule>(data);
				case "KalturaDrmProfileListResponse":
					return new ListResponse<DrmProfile>(data);
				case "KalturaPermissionListResponse":
					return new ListResponse<Permission>(data);
				case "KalturaMediaConcurrencyRuleListResponse":
					return new ListResponse<MediaConcurrencyRule>(data);
				case "KalturaAssetUserRuleListResponse":
					return new ListResponse<AssetUserRule>(data);
				case "KalturaCurrencyListResponse":
					return new ListResponse<Currency>(data);
				case "KalturaAssetRuleListResponse":
					return new ListResponse<AssetRule>(data);
				case "KalturaLanguageListResponse":
					return new ListResponse<Language>(data);
				case "KalturaMetaListResponse":
					return new ListResponse<Meta>(data);
				case "KalturaDeviceBrandListResponse":
					return new ListResponse<DeviceBrand>(data);
				case "KalturaCountryListResponse":
					return new ListResponse<Country>(data);
				case "KalturaOSSAdapterProfileListResponse":
					return new ListResponse<OSSAdapterProfile>(data);
				case "KalturaSearchHistoryListResponse":
					return new ListResponse<SearchHistory>(data);
				case "KalturaDeviceFamilyListResponse":
					return new ListResponse<DeviceFamily>(data);
				case "KalturaRegionListResponse":
					return new ListResponse<Region>(data);
				case "KalturaUserAssetRuleListResponse":
					return new ListResponse<UserAssetRule>(data);
				case "KalturaCDNAdapterProfileListResponse":
					return new ListResponse<CDNAdapterProfile>(data);
				case "KalturaExportTaskListResponse":
					return new ListResponse<ExportTask>(data);
				case "KalturaExternalChannelProfileListResponse":
					return new ListResponse<ExternalChannelProfile>(data);
				case "KalturaRecommendationProfileListResponse":
					return new ListResponse<RecommendationProfile>(data);
				case "KalturaRegistrySettingsListResponse":
					return new ListResponse<RegistrySettings>(data);
				case "KalturaParentalRuleListResponse":
					return new ListResponse<ParentalRule>(data);
				case "KalturaUserRoleListResponse":
					return new ListResponse<UserRole>(data);
			}
		
			return null;
		}
	}
}

